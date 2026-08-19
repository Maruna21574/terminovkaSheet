<?php require __DIR__ . '/header.php'; ?>
<div class="wrap">
    <div class="card">
        <h1 class="card__title"><?= h($event['name']) ?></h1>
        <p class="card__subtitle">Prezenčná registrácia bežca — vyplňte prosím všetky údaje.</p>

        <div id="queue-status" class="queue-status" hidden>
            <span id="queue-status-text"></span>
        </div>

        <div id="form-message" class="form-message" hidden></div>

        <form id="runner-form" novalidate>
            <input type="hidden" name="event_slug" value="<?= h($slug) ?>">

            <div class="field">
                <label for="meno" class="required">Meno</label>
                <input type="text" id="meno" name="meno" autocomplete="given-name" required>
            </div>

            <div class="field">
                <label for="priezvisko" class="required">Priezvisko</label>
                <input type="text" id="priezvisko" name="priezvisko" autocomplete="family-name" required>
            </div>

            <div class="field">
                <label for="pohlavie" class="required">Pohlavie</label>
                <select id="pohlavie" name="pohlavie" required>
                    <option value="" disabled selected>Vyberte...</option>
                    <option value="muz">Muž</option>
                    <option value="zena">Žena</option>
                </select>
            </div>

            <div class="field">
                <label for="narodenie" class="required">Dátum narodenia</label>
                <input type="text" id="narodenie" name="narodenie" placeholder="DD.MM.RRRR" inputmode="numeric" maxlength="10" required>
                <small class="field__hint">Formát: deň.mesiac.rok, napr. 1.1.1999</small>
            </div>

            <div class="field">
                <label for="klub">Klub <span class="field__optional">(nepovinné)</span></label>
                <input type="text" id="klub" name="klub" autocomplete="organization">
            </div>

            <div class="field">
                <label for="obec" class="required">Obec</label>
                <input type="text" id="obec" name="obec" autocomplete="address-level2" required>
            </div>

            <div class="field">
                <label for="trat" class="required">Trať</label>
                <input type="text" id="trat" name="trat" placeholder="napr. 10 km" required>
            </div>

            <div class="field field--checkbox">
                <label>
                    <input type="checkbox" id="suhlas_udaje" name="suhlas_udaje" required>
                    <span>Súhlasím so spracovaním osobných údajov.
                        <button type="button" class="link-btn" data-toggle="text-udaje">zobraziť podmienky</button>
                    </span>
                </label>
                <div id="text-udaje" class="consent-text" hidden>
                    <p><strong>TODO pre organizátora:</strong> nahraďte tento text skutočným znením súhlasu so
                    spracovaním osobných údajov (názov prevádzkovateľa, účel a rozsah spracovania, doba uchovávania,
                    práva dotknutej osoby podľa GDPR / zákona č. 18/2018 Z. z.).</p>
                </div>
            </div>

            <div class="field field--checkbox">
                <label>
                    <input type="checkbox" id="suhlas_podmienky" name="suhlas_podmienky" required>
                    <span>Prečítal(a) som si a súhlasím s podmienkami podujatia.
                        <button type="button" class="link-btn" data-toggle="text-podmienky">zobraziť podmienky</button>
                    </span>
                </label>
                <div id="text-podmienky" class="consent-text" hidden>
                    <p><strong>TODO pre organizátora:</strong> nahraďte tento text skutočnými podmienkami podujatia
                    (napr. štartovné, zdravotný stav účastníka, pravidlá preteku, fotografovanie a pod.).</p>
                </div>
            </div>

            <button type="submit" id="submit-btn" class="btn btn--primary btn--block">Odoslať</button>
        </form>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<?php require __DIR__ . '/footer.php'; ?>
