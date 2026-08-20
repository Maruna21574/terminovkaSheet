<?php require __DIR__ . '/header.php'; ?>
<div class="wrap">
    <div class="card">
        <h1 class="card__title"><?= h($event['name']) ?></h1>
        <p class="card__subtitle">Prezenčná registrácia bežca — vyplňte prosím všetky údaje.</p>

        <div id="queue-status" class="queue-status" hidden>
            <span id="queue-status-text"></span>
        </div>

        <form id="runner-form" novalidate>
            <input type="hidden" name="event_slug" value="<?= h($slug) ?>">

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
                <label for="narodenie_den" class="required">Dátum narodenia</label>
                <div class="field__row">
                    <select id="narodenie_den" name="narodenie_den" required aria-label="Deň narodenia">
                        <option value="" disabled selected>Deň</option>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="narodenie_mesiac" name="narodenie_mesiac" required aria-label="Mesiac narodenia">
                        <option value="" disabled selected>Mesiac</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>"><?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                    <input type="number" id="narodenie_rok" name="narodenie_rok" placeholder="Rok" inputmode="numeric" min="1900" max="<?= (int) date('Y') ?>" required aria-label="Rok narodenia">
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

            <div id="form-message" class="form-message" hidden></div>
        </form>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<?php require __DIR__ . '/footer.php'; ?>
