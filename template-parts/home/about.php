<?php
/**
 * Секция главной: about.
 *
 * @package promix
 */

?>
<section class="section section--divided about" id="about">
        <div class="container">

            <h2 class="section__title about__title">О компании PROMIX</h2>

            <div class="about__intro">
                <img class="about__photo" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/office/1.png' ) ); ?>"
                     alt="Торговый зал малярного центра PROMIX" loading="lazy" width="1280" height="853">

                <div class="about__copy">
                    <p class="about__lead">PROMIX — профессиональный малярный центр в Казани для мастеров, строительных компаний, дизайнеров и частных клиентов.</p>

                    <p class="about__text">В зале — лакокрасочные материалы, инструмент и оборудование от ведущих производителей. Помогаем подобрать решение под задачу: от ремонта квартиры до большого объекта.</p>

                    <dl class="facts">
                        <div class="fact">
                            <span class="fact__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11l3 3L22 4"/></g></svg>
                            </span>
                            <div class="fact__body">
                                <dt class="fact__value">10+ лет</dt>
                                <dd class="fact__label">опыта в стройсфере</dd>
                            </div>
                        </div>

                        <div class="fact">
                            <span class="fact__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"/></svg>
                            </span>
                            <div class="fact__body">
                                <dt class="fact__value">4,9 из 5</dt>
                                <dd class="fact__label">44 отзыва в 2ГИС</dd>
                            </div>
                        </div>

                        <div class="fact">
                            <span class="fact__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73zm1 .27V12"/><path d="M3.29 7L12 12l8.71-5M7.5 4.27l9 5.15"/></g></svg>
                            </span>
                            <div class="fact__body">
                                <dt class="fact__value">1 800+</dt>
                                <dd class="fact__label">товаров в наличии</dd>
                            </div>
                        </div>

                        <div class="fact">
                            <span class="fact__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></g></svg>
                            </span>
                            <div class="fact__body">
                                <dt class="fact__value">Казань</dt>
                                <dd class="fact__label">Габдуллы Тукая, 91</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Полоса про семинары: рисунки инструментов — фон, поэтому aria-hidden -->
        <div class="seminars">
            <div class="seminars__decor" aria-hidden="true">
                <svg class="doodle doodle--paint-bucket" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M11 7L6 2m12.992 10H2.041m19.104 6.38A3.34 3.34 0 0 1 20 16.5a3.3 3.3 0 0 1-1.145 1.88c-.575.46-.855 1.02-.855 1.595A2 2 0 0 0 20 22a2 2 0 0 0 2-2.025c0-.58-.285-1.13-.855-1.595M8.5 4.5l2.148-2.148a1.205 1.205 0 0 1 1.704 0l7.296 7.296a1.205 1.205 0 0 1 0 1.704l-7.592 7.592a3.615 3.615 0 0 1-5.112 0l-3.888-3.888a3.615 3.615 0 0 1 0-5.112L5.67 7.33"/></svg>
                <svg class="doodle doodle--paint-roller" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="6" x="2" y="2" rx="2"/><path d="M10 16v-2a2 2 0 0 1 2-2h8a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="4" height="6" x="8" y="16" rx="1"/></g></svg>
                <svg class="doodle doodle--brush" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m11 10l3 3m-7.5 8A3.5 3.5 0 1 0 3 17.5a2.62 2.62 0 0 1-.708 1.792A1 1 0 0 0 3 21z"/><path d="M9.969 17.031L21.378 5.624a1 1 0 0 0-3.002-3.002L6.967 14.031"/></g></svg>
                <svg class="doodle doodle--palette" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a1 1 0 0 1 0-20a10 9 0 0 1 10 9a5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/></g></svg>
                <svg class="doodle doodle--spray-can" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M3 3h.01M7 5h.01M11 7h.01M3 7h.01M7 9h.01M3 11h.01M15 5h4v4h-4zm4 4l2 2v10c0 .6-.4 1-1 1h-6c-.6 0-1-.4-1-1V11l2-2m-2 5l8-2m-8 7l8-2"/></svg>
                <svg class="doodle doodle--ruler" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Zm-6.8-2.8l2-2m-5-1l2-2m-5-1l2-2m7 11l2-2"/></svg>
            </div>

            <div class="container seminars__inner">
                <h3 class="seminars__title">Мы делаем пространство, в которое мастеру удобно заезжать снова</h3>
                <p class="seminars__text">Проводим семинары и мастер-классы в учебном классе, обучаем работе с материалами и оборудованием, помогаем в подборе и комплектации объектов.</p>
            </div>
        </div>
    </section>
