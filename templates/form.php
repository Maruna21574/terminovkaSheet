<?php require __DIR__ . '/header.php'; ?>
<div class="wrap">
    <div class="card">
        <h1 class="card__title"><?= h($event['name']) ?></h1>
        <p class="card__subtitle">Prezenčná registrácia bežca — vyplňte prosím všetky údaje.</p>

        <form id="runner-form" novalidate>
            <input type="hidden" name="event_slug" value="<?= h($slug) ?>">

            <!-- Honeypot proti botom - skutoční ľudia toto pole nevidia ani nevyplnia. -->
            <div class="field-honeypot" aria-hidden="true">
                <label for="website">Nechajte prázdne</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="field">
                <label for="meno" class="required">Meno</label>
                <input type="text" id="meno" name="meno" autocomplete="given-name" required>
                <span class="field__error" id="meno-error" hidden></span>
            </div>

            <div class="field">
                <label for="priezvisko" class="required">Priezvisko</label>
                <input type="text" id="priezvisko" name="priezvisko" autocomplete="family-name" required>
                <span class="field__error" id="priezvisko-error" hidden></span>
            </div>

            <div class="field">
                <label for="pohlavie" class="required">Pohlavie</label>
                <select id="pohlavie" name="pohlavie" required>
                    <option value="" disabled selected>Vyberte...</option>
                    <option value="muz">Muž</option>
                    <option value="zena">Žena</option>
                </select>
                <span class="field__error" id="pohlavie-error" hidden></span>
            </div>

            <div class="field">
                <label for="narodenie_den" class="required field__label--spaced">Dátum narodenia</label>
                <div class="field__row">
                    <div class="field__row-item">
                        <span class="field__row-label">Deň</span>
                        <select id="narodenie_den" name="narodenie_den" required aria-label="Deň narodenia">
                            <option value="" disabled selected>—</option>
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="field__row-item">
                        <span class="field__row-label">Mesiac</span>
                        <select id="narodenie_mesiac" name="narodenie_mesiac" required aria-label="Mesiac narodenia">
                            <option value="" disabled selected>—</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="field__row-item field__row-item--year">
                        <span class="field__row-label">Rok</span>
                        <input type="number" id="narodenie_rok" name="narodenie_rok" placeholder="RRRR" inputmode="numeric" min="1900" max="<?= (int) date('Y') ?>" required aria-label="Rok narodenia">
                    </div>
                </div>
                <span class="field__error" id="narodenie-error" hidden></span>
            </div>

            <div class="field">
                <label for="klub">Klub <span class="field__optional">(nepovinné)</span></label>
                <input type="text" id="klub" name="klub" autocomplete="organization">
            </div>

            <div class="field">
                <label for="obec">Obec <span class="field__optional">(nepovinné)</span></label>
                <input type="text" id="obec" name="obec" autocomplete="address-level2">
            </div>

            <div class="field">
                <label for="trat" class="required">Trať</label>
                <input type="text" id="trat" name="trat" placeholder="napr. 10 km" required>
                <span class="field__error" id="trat-error" hidden></span>
            </div>

            <div class="field field--checkbox">
                <label>
                    <input type="checkbox" id="suhlas_udaje" name="suhlas_udaje" required>
                    <span>Súhlasím so spracovaním osobných údajov.
                        <?php if (!empty($event['gdpr_url'])): ?>
                            <a href="<?= h($event['gdpr_url']) ?>" target="_blank" rel="noopener" class="link-btn">zobraziť podmienky</a>
                        <?php else: ?>
                            <button type="button" class="link-btn" data-toggle="text-udaje">zobraziť podmienky</button>
                        <?php endif; ?>
                    </span>
                </label>
                <?php if (empty($event['gdpr_url'])): ?>
                <div id="text-udaje" class="consent-text" hidden>
                    <p><strong>TODO pre organizátora:</strong> nahraďte tento text skutočným znením súhlasu so
                    spracovaním osobných údajov (názov prevádzkovateľa, účel a rozsah spracovania, doba uchovávania,
                    práva dotknutej osoby podľa GDPR / zákona č. 18/2018 Z. z.), alebo v administrácii podujatia
                    zadajte URL adresu s podmienkami namiesto tohto textu.</p>
                </div>
                <?php endif; ?>
                <span class="field__error" id="suhlas_udaje-error" hidden></span>
            </div>

            <div class="field field--checkbox">
                <label>
                    <input type="checkbox" id="suhlas_podmienky" name="suhlas_podmienky" required>
                    <span>Prečítal(a) som si a súhlasím s podmienkami podujatia.
                        <?php if (!empty($event['terms_url'])): ?>
                            <a href="<?= h($event['terms_url']) ?>" target="_blank" rel="noopener" class="link-btn">zobraziť podmienky</a>
                        <?php else: ?>
                            <button type="button" class="link-btn" data-toggle="text-podmienky">zobraziť podmienky</button>
                        <?php endif; ?>
                    </span>
                </label>
                <?php if (empty($event['terms_url'])): ?>
                <div id="text-podmienky" class="consent-text" hidden>
                    <p><strong>TODO pre organizátora:</strong> nahraďte tento text skutočnými podmienkami podujatia
                    (napr. štartovné, zdravotný stav účastníka, pravidlá preteku, fotografovanie a pod.), alebo
                    v administrácii podujatia zadajte URL adresu s podmienkami namiesto tohto textu.</p>
                </div>
                <?php endif; ?>
                <span class="field__error" id="suhlas_podmienky-error" hidden></span>
            </div>

            <button type="submit" id="submit-btn" class="btn btn--primary btn--block">Odoslať</button>

            <div class="status-stack">
                <div id="form-message" class="form-message" hidden></div>
                <div id="queue-status" class="queue-status" hidden>
                    <span class="queue-status__icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 7v5l3.5 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span id="queue-status-text"></span>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="pickup-modal" class="modal" hidden>
    <div class="modal__overlay" data-modal-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pickup-modal-title">
        <div class="modal__icon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 12V8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v4M4 12v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4M4 12h16M9 6V4M15 6V4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2 id="pickup-modal-title" class="modal__title">Registrácia odoslaná!</h2>
        <p class="modal__text">Vyzdvihni si číslo a povedz, že si z formulára.</p>
        <button type="button" class="btn btn--success btn--block" data-modal-close>Rozumiem</button>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<?php require __DIR__ . '/footer.php'; ?>
