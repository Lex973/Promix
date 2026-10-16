"""Inspect a 1C OData endpoint with GET requests only; never update 1C or Woo."""

import argparse
import base64
from datetime import datetime, timezone
import getpass
import http.client
import json
import os
from pathlib import Path
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import warnings
import xml.etree.ElementTree as ET


MAX_BYTES = 8 * 1024 * 1024
EDM_NAMESPACES = {
    "http://schemas.microsoft.com/ado/2006/04/edm",
    "http://schemas.microsoft.com/ado/2007/05/edm",
    "http://schemas.microsoft.com/ado/2008/09/edm",
    "http://schemas.microsoft.com/ado/2009/11/edm",
    "http://docs.oasis-open.org/odata/ns/edm",
}


class ProbeError(Exception):
    pass


def normalize_base(value):
    value = value.strip().rstrip("/")
    try:
        parsed = urllib.parse.urlsplit(value)
        parsed.port
    except ValueError:
        raise ProbeError("Invalid endpoint URL.") from None
    path = urllib.parse.unquote(parsed.path)
    if (
        parsed.scheme != "https"
        or not parsed.hostname
        or parsed.username is not None
        or parsed.password is not None
        or parsed.query
        or parsed.fragment
        or "?" in value
        or "#" in value
        or any(ord(c) < 33 for c in value + path)
        or "\\" in path
        or any(part in {".", ".."} for part in path.split("/"))
        or not path.endswith("/odata/standard.odata")
    ):
        raise ProbeError("Use an HTTPS endpoint ending in /odata/standard.odata/, without credentials or query parameters.")
    try:
        hostname = parsed.hostname.encode("idna").decode("ascii")
    except UnicodeError:
        raise ProbeError("Invalid endpoint hostname.") from None
    host = f"[{hostname}]" if ":" in hostname else hostname
    if parsed.port is not None:
        host += f":{parsed.port}"
    encoded_path = urllib.parse.quote(parsed.path, safe="/%:@-._~!$&'()*+,;=")
    return urllib.parse.urlunsplit(("https", host, encoded_path + "/", "", ""))


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        raise ProbeError("HTTP redirect refused; ask for the direct HTTPS OData address.")


class Client:
    def __init__(self, base, user, password):
        self.base = normalize_base(base)
        if not user or ":" in user or not password:
            raise ProbeError("A nonempty username (without ':') and password are required.")
        try:
            token = base64.b64encode(f"{user}:{password}".encode("utf-8")).decode("ascii")
        except UnicodeError:
            raise ProbeError("Credentials must be valid UTF-8 text.") from None
        self.authorization = "Basic " + token
        self.opener = urllib.request.build_opener(NoRedirect())

    def get(self, resource, params=None, accept="application/json"):
        if resource != "$metadata" and not re.fullmatch(r"\w+", resource):
            raise ProbeError("Only metadata and a named entity collection can be read.")
        url = self.base + urllib.parse.quote(resource, safe="$")
        if params:
            url += "?" + urllib.parse.urlencode(params)
        request = urllib.request.Request(url, method="GET", headers={
            "Authorization": self.authorization,
            "Accept": accept,
            "User-Agent": "PROMIX-OData-Probe/1.0",
        })
        started = time.monotonic()
        try:
            with self.opener.open(request, timeout=30) as response:
                if response.status != 200:
                    raise ProbeError("Expected HTTP 200.")
                body = response.read(MAX_BYTES + 1)
        except urllib.error.HTTPError as error:
            error.close()
            hints = {
                401: "Check credentials and OData role.",
                403: "Check permissions and exposed objects.",
                404: "Check endpoint and resource name.",
                429: "Service rate limit; retry later.",
            }
            raise ProbeError(f"HTTP {error.code}. {hints.get(error.code, 'Service request failed.')}") from None
        except (urllib.error.URLError, TimeoutError, OSError, http.client.HTTPException, ValueError):
            raise ProbeError("Network, timeout or TLS verification failure; no response was saved.") from None
        if len(body) > MAX_BYTES:
            raise ProbeError("Response exceeds the 8 MiB limit.")
        return body, round(time.monotonic() - started, 3)


def parse_metadata(body):
    if len(body) > MAX_BYTES:
        raise ProbeError("Metadata exceeds the 8 MiB limit.")
    # Forbid DTD/entities, including XML encodings with interleaved NUL bytes.
    upper = body.replace(b"\x00", b"").upper()
    if b"<!DOCTYPE" in upper or b"<!ENTITY" in upper:
        raise ProbeError("DTD/entity declarations are not accepted.")
    try:
        root = ET.fromstring(body)
    except (ET.ParseError, ValueError, LookupError):
        raise ProbeError("Response is not valid XML metadata.") from None
    schemas = [node for node in root.iter() if any(
        node.tag == f"{{{ns}}}Schema" for ns in EDM_NAMESPACES
    )]
    def valid_name(value):
        return isinstance(value, str) and re.fullmatch(r"[^\W\d]\w*", value) is not None

    def qualified_name(value):
        return isinstance(value, str) and all(valid_name(part) for part in value.split("."))

    aliases = {}
    for schema in schemas:
        prefix = schema.get("Namespace")
        if not qualified_name(prefix):
            raise ProbeError("Metadata schema has an invalid namespace.")
        alias = schema.get("Alias")
        if alias is not None:
            if not valid_name(alias) or (alias in aliases and aliases[alias] != prefix):
                raise ProbeError("Metadata schema has an invalid or conflicting alias.")
            aliases[alias] = prefix

    def canonical_type(value):
        if not qualified_name(value) or "." not in value:
            raise ProbeError("Metadata entity type reference is invalid.")
        namespace, name = value.rsplit(".", 1)
        return aliases.get(namespace, namespace) + "." + name

    types = {}
    entities = []
    entity_names = set()
    for schema in schemas:
        ns = schema.tag[1:].split("}")[0]
        prefix = schema.get("Namespace")
        for entity_type in schema.findall(f"{{{ns}}}EntityType"):
            name = entity_type.get("Name")
            if not valid_name(name) or prefix + "." + name in types:
                raise ProbeError("Metadata entity type name is invalid or duplicated.")
            properties = {}
            for prop in entity_type.findall(f"{{{ns}}}Property"):
                prop_name, prop_type = prop.get("Name"), prop.get("Type")
                if not valid_name(prop_name) or not prop_type or prop_name in properties:
                    raise ProbeError("Metadata property has missing, invalid or duplicate attributes.")
                properties[prop_name] = prop_type
            base_type = entity_type.get("BaseType")
            types[prefix + "." + name] = {"properties": properties,
                                        "base": canonical_type(base_type) if base_type is not None else None}
        for container in schema.findall(f"{{{ns}}}EntityContainer"):
            for entity in container.findall(f"{{{ns}}}EntitySet"):
                name = entity.get("Name")
                if not valid_name(name) or name in entity_names:
                    raise ProbeError("Metadata collection name is missing, invalid or duplicated.")
                entity_names.add(name)
                entities.append({"name": name, "type": canonical_type(entity.get("EntityType"))})
    if not entities:
        raise ProbeError("No OData entity collections found; check the response and exposed objects.")
    resolved = {}

    def properties_for(name, visiting):
        if name in resolved:
            return resolved[name]
        if name not in types or name in visiting or len(visiting) >= 32:
            raise ProbeError("Metadata contains an unresolved or cyclic entity type reference.")
        entity_type = types[name]
        properties = {}
        if entity_type["base"] is not None:
            properties.update(properties_for(entity_type["base"], visiting | {name}))
        if properties.keys() & entity_type["properties"].keys():
            raise ProbeError("Metadata contains conflicting inherited properties.")
        properties.update(entity_type["properties"])
        resolved[name] = properties
        return properties

    for entity in entities:
        entity["properties"] = properties_for(entity["type"], set())
    return sorted(entities, key=lambda entity: entity["name"])


def reject_json_constant(value):
    raise ProbeError("Nonfinite JSON numbers are not accepted.")


def unique_json_object(pairs):
    result = {}
    for name, value in pairs:
        if name in result:
            raise ProbeError("Duplicate JSON property names are not accepted.")
        result[name] = value
    return result


def read_password():
    try:
        with warnings.catch_warnings():
            warnings.simplefilter("error", getpass.GetPassWarning)
            return getpass.getpass("Service password: ")
    except getpass.GetPassWarning:
        raise ProbeError("Hidden password input is unavailable; use a supported terminal or process environment.") from None


def sample(client, entities, entity_name, fields, top):
    entity = next((entity for entity in entities if entity["name"] == entity_name), None)
    if entity is None:
        raise ProbeError("Requested collection is absent from metadata.")
    if not fields or any(field not in entity["properties"] for field in fields):
        raise ProbeError("Select explicit property names present in this collection's metadata.")
    if not 1 <= top <= 10:
        raise ProbeError("Sample size must be between 1 and 10.")
    body, elapsed = client.get(entity_name, {"$format": "json", "$top": top, "$select": ",".join(fields)})
    try:
        payload = json.loads(body, parse_float=str, parse_constant=reject_json_constant,
                             object_pairs_hook=unique_json_object)
    except (ValueError, UnicodeError, RecursionError):
        raise ProbeError("Sample response is not valid JSON.") from None
    rows = payload.get("value") if isinstance(payload, dict) else None
    if not isinstance(rows, list) or len(rows) > top or any(not isinstance(row, dict) for row in rows):
        raise ProbeError("Unexpected OData collection response; sample was not saved.")
    if any(any(field not in row for field in fields) for row in rows):
        raise ProbeError("A selected property is missing from a sample row; sample was not saved.")
    # Save selected fields only, even if a server ignores $select.
    return {"entity": entity_name, "fields": fields, "elapsed_seconds": elapsed,
            "rows": [{field: row[field] for field in fields} for row in rows]}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--offline-metadata", type=Path, help="Inspect a local XML file without network or credentials")
    parser.add_argument("--entity", help="Explicit collection name from metadata; read at most 10 rows")
    parser.add_argument("--fields", help="Comma-separated property names to include in the sample")
    parser.add_argument("--top", type=int, default=3, choices=range(1, 11))
    args = parser.parse_args()
    if bool(args.entity) != bool(args.fields) or (args.offline_metadata and args.entity):
        parser.error("Use --entity and --fields together, only in live mode.")
    try:
        client = None
        elapsed = None
        if args.offline_metadata:
            with args.offline_metadata.open("rb") as source:
                metadata = source.read(MAX_BYTES + 1)
        else:
            base = os.environ.get("ONEC_ODATA_URL") or input("OData HTTPS address: ")
            base = normalize_base(base)
            user = os.environ.get("ONEC_ODATA_USER") or input("Service username: ")
            password = os.environ.get("ONEC_ODATA_PASSWORD") or read_password()
            client = Client(base, user, password)
            metadata, elapsed = client.get("$metadata", accept="application/xml")
        entities = parse_metadata(metadata)
        report = {"created_at": datetime.now(timezone.utc).isoformat(),
                  "mode": "offline" if args.offline_metadata else "live",
                  "metadata_seconds": elapsed, "entities": entities}
        if args.entity:
            fields = [field.strip() for field in args.fields.split(",")]
            report["sample"] = sample(client, entities, args.entity, fields, args.top)
        try:
            serialized = json.dumps(report, ensure_ascii=False, indent=2, allow_nan=False)
            serialized.encode("utf-8")
        except (ValueError, UnicodeError, RecursionError):
            raise ProbeError("Report data cannot be represented as valid JSON; no report was saved.") from None
        output = Path(__file__).resolve().parents[1] / "docs" / "integration-1c" / "odata-runs"
        output.mkdir(parents=True, exist_ok=True)
        destination = output / (datetime.now(timezone.utc).strftime("%Y%m%d-%H%M%S-%f") + ".json")
        with destination.open("x", encoding="utf-8") as target:
            target.write(serialized)
        print(f"Collections: {len(entities)}. Report: {destination}")
        print("GET only. No 1C or WooCommerce records were modified.")
    except (ProbeError, OSError, EOFError) as error:
        message = str(error) if isinstance(error, ProbeError) else "Could not read input or write the local report."
        print(message, file=sys.stderr)
        return 1
    except KeyboardInterrupt:
        print("Probe cancelled.", file=sys.stderr)
        return 130
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
