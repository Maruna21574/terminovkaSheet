<!doctype html>
<html lang="sk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= h(asset_url('assets/css/style.css')) ?>">
<meta name="theme-color" content="#141518">
</head>
<body>
<header class="site-header">
    <div class="site-header__inner">
        <a href="https://www.terminovka.sk" target="_blank" rel="noopener" class="site-header__logo-link">
            <img src="/assets/img/logo-full-white.svg" alt="Terminovka" class="site-header__logo-img">
        </a>
        <div class="site-header__socials">
            <a href="https://www.instagram.com/terminovkask" target="_blank" rel="noopener" class="site-header__social-link" aria-label="Instagram">
                <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="bg" d="M3 7C3 4.79086 4.79086 3 7 3H17C19.2091 3 21 4.79086 21 7V17C21 19.2091 19.2091 21 17 21H7C4.79086 21 3 19.2091 3 17V7Z" fill="#D6243D"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M17.5 7.5C18.0523 7.5 18.5 7.05228 18.5 6.5C18.5 5.94772 18.0523 5.5 17.5 5.5C16.9477 5.5 16.5 5.94772 16.5 6.5C16.5 7.05228 16.9477 7.5 17.5 7.5ZM10 12C10 10.8954 10.8954 10 12 10C13.1046 10 14 10.8954 14 12C14 13.1046 13.1046 14 12 14C10.8954 14 10 13.1046 10 12ZM12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8Z" fill="#ffffff"/>
                </svg>
            </a>
            <a href="https://www.facebook.com/profile.php?id=100067085184112" target="_blank" rel="noopener" class="site-header__social-link" aria-label="Facebook">
                <svg width="25" height="25" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="bg" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="#D6243D"/>
                    <path d="M13 21.9506C12.6711 21.9833 12.3375 22 12 22C11.6625 22 11.3289 21.9833 11 21.9506V14H10C9.44772 14 9 13.5523 9 13C9 12.4477 9.44772 12 10 12H11V10C11 8.34315 12.3431 7 14 7H15C15.5523 7 16 7.44772 16 8C16 8.55228 15.5523 9 15 9H14C13.4477 9 13 9.44772 13 10V12H15C15.5523 12 16 12.4477 16 13C16 13.5523 15.5523 14 15 14H13V21.9506Z" fill="#ffffff"/>
                </svg>
            </a>
            <a href="https://strava.app.link/rcrPNDaStSb" target="_blank" rel="noopener" class="site-header__social-link" aria-label="Strava">
                <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="bg" d="M18.3 3H5.7C4.20883 3 3 4.20883 3 5.7V18.3C3 19.7912 4.20883 21 5.7 21H18.3C19.7912 21 21 19.7912 21 18.3V5.7C21 4.20883 19.7912 3 18.3 3Z" fill="#D6243D"/>
                    <path d="M7.21875 13.125L11.1562 4.96875L15.0938 13.125H12.5625L11.1562 9.75L9.75 13.125H7.21875Z" fill="white"/>
                    <path d="M12.8438 13.125L13.9688 15.6562L15.0938 13.125H16.7812L13.9688 19.0312L11.1562 13.125H12.8438Z" fill="white" fill-opacity="0.5"/>
                </svg>
            </a>
        </div>
    </div>
</header>
<main class="site-main">
