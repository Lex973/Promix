<?php
/**
 * Секция главной: brands.
 *
 * @package promix
 */

?>
<section class="section brands" id="brands" data-brands>
        <div class="container">
            <div class="section__head">
                <div>
                    <p class="kicker">Бренды</p>
                    <h2 class="section__title">Работаем с проверенными<br>производителями</h2>
                </div>
                <p class="section__lead">Держим марки, которые мастера берут в работу постоянно. Привозим их стабильно и подсказываем, как с ними работать.</p>
            </div>
        </div>

        <!-- Две бегущие строки едут навстречу друг другу; плитки подставляет brands.js -->
        <div class="brands__rows">
            <div class="brands__row">
                <div class="brands__track" data-brands-track></div>
            </div>
            <div class="brands__row brands__row--reverse">
                <div class="brands__track" data-brands-track></div>
            </div>
        </div>

        <div class="container">
            <div class="brands__cta">
                <p class="brands__note">Не нашли нужную марку?</p>
                <a class="btn btn--outline brands__btn" href="#contacts">
                    Спросить у технолога
                    <span class="brands__btn-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M13 6l6 6-6 6"></path>
                        </svg>
                    </span>
                </a>
            </div>
        </div>
    </section>
