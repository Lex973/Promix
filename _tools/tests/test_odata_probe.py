"""Local protocol tests; no real 1C credentials or remote requests."""

import base64
import importlib.util
import json
from pathlib import Path
import threading
import unittest
import urllib.request
import warnings
from unittest.mock import patch
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

spec = importlib.util.spec_from_file_location("odata_probe", Path(__file__).resolve().parents[1] / "odata_probe.py")
probe = importlib.util.module_from_spec(spec)
spec.loader.exec_module(probe)

METADATA = """<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx xmlns:edmx="http://schemas.microsoft.com/ado/2007/06/edmx">
<edmx:DataServices><Schema xmlns="http://schemas.microsoft.com/ado/2008/09/edm" Namespace="StandardODATA">
<EntityType Name="Catalog_Номенклатура"><Property Name="Ref_Key" Type="Edm.Guid"/>
<Property Name="Description" Type="Edm.String"/><Property Name="Цена" Type="Edm.Decimal"/></EntityType>
<EntityContainer Name="Default"><EntitySet Name="Catalog_Номенклатура" EntityType="StandardODATA.Catalog_Номенклатура"/></EntityContainer>
</Schema></edmx:DataServices></edmx:Edmx>""".encode("utf-8")


class Handler(BaseHTTPRequestHandler):
    requests = []
    mode = "normal"
    sample_body = None

    def log_message(self, *args):
        pass

    def do_GET(self):
        self.requests.append((self.command, self.path, self.headers.get("Authorization")))
        if self.mode == "redirect":
            self.send_response(302)
            self.send_header("Location", "/must-not-follow")
            self.end_headers()
            return
        if self.mode == "unauthorized":
            self.send_response(401)
            self.end_headers()
            self.wfile.write(b"fake-secret-must-not-be-printed")
            return
        if self.mode == "truncated":
            self.send_response(200)
            self.send_header("Transfer-Encoding", "chunked")
            self.end_headers()
            self.wfile.write(b"4\r\nxy")
            self.close_connection = True
            return
        self.send_response(200)
        self.end_headers()
        if self.path.endswith("$metadata"):
            self.wfile.write(METADATA)
        elif self.sample_body is not None:
            self.wfile.write(self.sample_body)
        else:
            self.wfile.write(br'{"value":[{"Ref_Key":"guid-example","Description":"Example","\u0426\u0435\u043d\u0430":1074.84,"Private":"omit"}]}')


class ProbeTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.server = ThreadingHTTPServer(("127.0.0.1", 0), Handler)
        cls.thread = threading.Thread(target=cls.server.serve_forever, daemon=True)
        cls.thread.start()

    @classmethod
    def tearDownClass(cls):
        cls.server.shutdown()
        cls.server.server_close()
        cls.thread.join()

    def setUp(self):
        Handler.requests = []
        Handler.mode = "normal"
        Handler.sample_body = None
        self.client = probe.Client("https://example.invalid/a/ea/1/odata/standard.odata/", "test-user", "test-password")
        # Production validates HTTPS. Only the local test harness replaces the base.
        self.client.base = f"http://127.0.0.1:{self.server.server_port}/odata/standard.odata/"
        self.client.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), probe.NoRedirect())

    def test_metadata_and_selected_sample_use_get(self):
        metadata, _ = self.client.get("$metadata", accept="application/xml")
        entities = probe.parse_metadata(metadata)
        result = probe.sample(self.client, entities, "Catalog_Номенклатура", ["Ref_Key", "Цена"], 1)
        self.assertEqual(result["rows"], [{"Ref_Key": "guid-example", "Цена": "1074.84"}])
        self.assertTrue(all(request[0] == "GET" for request in Handler.requests))
        auth = "Basic " + base64.b64encode(b"test-user:test-password").decode("ascii")
        self.assertTrue(all(request[2] == auth for request in Handler.requests))
        self.assertIn("%24top=1", Handler.requests[1][1])
        self.assertNotIn("test-password", json.dumps(result))

    def test_redirect_is_not_followed(self):
        Handler.mode = "redirect"
        with self.assertRaisesRegex(probe.ProbeError, "redirect refused"):
            self.client.get("$metadata")
        self.assertEqual(len(Handler.requests), 1)

    def test_auth_error_does_not_expose_body(self):
        Handler.mode = "unauthorized"
        with self.assertRaises(probe.ProbeError) as caught:
            self.client.get("$metadata")
        self.assertIn("401", str(caught.exception))
        self.assertNotIn("fake-secret", str(caught.exception))

    def test_truncated_chunked_response_is_a_safe_error(self):
        Handler.mode = "truncated"
        with self.assertRaisesRegex(probe.ProbeError, "Network"):
            self.client.get("$metadata")

    def test_missing_selected_field_is_rejected(self):
        Handler.sample_body = b'{"value":[{"Ref_Key":"guid-example"}]}'
        with self.assertRaisesRegex(probe.ProbeError, "missing"):
            probe.sample(self.client, probe.parse_metadata(METADATA), "Catalog_Номенклатура", ["Цена"], 1)

    def test_nonfinite_numbers_and_duplicate_json_keys_are_rejected(self):
        for value in [b"NaN", b"Infinity", b"-Infinity"]:
            Handler.sample_body = br'{"value":[{"\u0426\u0435\u043d\u0430":' + value + b"}]}"
            with self.subTest(value=value), self.assertRaisesRegex(probe.ProbeError, "Nonfinite"):
                probe.sample(self.client, probe.parse_metadata(METADATA), "Catalog_Номенклатура", ["Цена"], 1)
        Handler.sample_body = br'{"value":[{"\u0426\u0435\u043d\u0430":1,"\u0426\u0435\u043d\u0430":2}]}'
        with self.assertRaisesRegex(probe.ProbeError, "Duplicate"):
            probe.sample(self.client, probe.parse_metadata(METADATA), "Catalog_Номенклатура", ["Цена"], 1)

    def test_deep_json_is_a_safe_error(self):
        Handler.sample_body = br'{"value":[{"\u0426\u0435\u043d\u0430":' + b"[" * 25000 + b"0" + b"]" * 25000 + b"}]}"
        with self.assertRaisesRegex(probe.ProbeError, "valid JSON"):
            probe.sample(self.client, probe.parse_metadata(METADATA), "Catalog_Номенклатура", ["Цена"], 1)

    def test_unknown_xml_encoding_is_a_safe_error(self):
        with self.assertRaisesRegex(probe.ProbeError, "XML metadata"):
            probe.parse_metadata(b'<?xml version="1.0" encoding="bad-encoding"?><root/>')

    def test_password_prompt_does_not_fall_back_to_visible_input(self):
        def unsupported_terminal(*args):
            warnings.warn("No hidden input", probe.getpass.GetPassWarning)
            self.fail("Visible password input must never be reached")
        with patch.object(probe.getpass, "getpass", side_effect=unsupported_terminal):
            with self.assertRaisesRegex(probe.ProbeError, "Hidden password input"):
                probe.read_password()

    def test_unknown_entity_and_fields_do_not_trigger_requests(self):
        entities = probe.parse_metadata(METADATA)
        for entity, fields in [("Unknown", ["Цена"]), ("Catalog_Номенклатура", ["Unknown"])]:
            with self.assertRaises(probe.ProbeError):
                probe.sample(self.client, entities, entity, fields, 1)
        self.assertEqual(Handler.requests, [])

    def test_endpoint_validation(self):
        for value in ["http://example.com/odata/standard.odata", "https://user:password@example.com/odata/standard.odata",
                      "https://example.com/odata/standard.odata?q=1", "https://example.com/odata/standard.odata#x",
                      "https://example.com/odata/standard.odata?", "https://example.com/odata/standard.odata#",
                      "https://example.com/a/%2e%2e/odata/standard.odata", "https://example.com/other"]:
            with self.subTest(value=value), self.assertRaises(probe.ProbeError):
                probe.normalize_base(value)
        self.assertEqual(probe.normalize_base("https://example.com/a/odata/standard.odata/"),
                         "https://example.com/a/odata/standard.odata/")
        self.assertEqual(probe.normalize_base("https://example.com/база/odata/standard.odata"),
                         "https://example.com/%D0%B1%D0%B0%D0%B7%D0%B0/odata/standard.odata/")

    def test_metadata_rejects_dtd_and_non_metadata(self):
        dtd = '<!DOCTYPE root [<!ENTITY x "test">]><root/>'
        for body in [dtd.encode(), dtd.encode("utf-16"), b"<html>Login</html>", b"broken"]:
            with self.assertRaises(probe.ProbeError):
                probe.parse_metadata(body)

    def test_malformed_metadata_attributes_and_duplicate_names(self):
        replacements = [
            (b'Name="Catalog_', b'Name="../Catalog_'),
            (b'Name="Ref_Key"', b'Name=""'),
            (b'Type="Edm.Guid"', b'Type=""'),
            (b'EntityType="StandardODATA.', b'EntityType="Missing.'),
            (b'Name="Description"', b'Name="Ref_Key"'),
        ]
        for before, after in replacements:
            with self.subTest(after=after), self.assertRaises(probe.ProbeError):
                probe.parse_metadata(METADATA.replace(before, after))
        missing_set = METADATA.replace(
            'Name="Catalog_Номенклатура" EntityType="StandardODATA.Catalog_Номенклатура"'.encode(), b""
        )
        with self.assertRaises(probe.ProbeError):
            probe.parse_metadata(missing_set)

    def test_metadata_alias_and_inherited_properties(self):
        body = METADATA.replace(b'Namespace="StandardODATA"', b'Namespace="StandardODATA" Alias="S"')
        body = body.replace(b'EntityType="StandardODATA.', b'EntityType="S.')
        body = body.replace(b'<EntityType Name=',
                            b'<EntityType Name="Base"><Property Name="Code" Type="Edm.String"/></EntityType><EntityType BaseType="S.Base" Name=', 1)
        entities = probe.parse_metadata(body)
        self.assertEqual(entities[0]["type"], "StandardODATA.Catalog_Номенклатура")
        self.assertEqual(entities[0]["properties"]["Code"], "Edm.String")

    def test_cyclic_metadata_inheritance_is_rejected(self):
        body = METADATA.replace(b'<EntityType Name=',
                                '<EntityType BaseType="StandardODATA.Catalog_Номенклатура" Name='.encode(), 1)
        with self.assertRaisesRegex(probe.ProbeError, "cyclic"):
            probe.parse_metadata(body)

    def test_response_size_limit(self):
        with self.assertRaisesRegex(probe.ProbeError, "8 MiB"):
            probe.parse_metadata(b"x" * (probe.MAX_BYTES + 1))


if __name__ == "__main__":
    unittest.main()
