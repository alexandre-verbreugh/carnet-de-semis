/*
 * Seul script de l'application : il replie la navigation derriere un bouton
 * sur petit ecran. Sans lui, la navigation reste affichee et tout fonctionne.
 *
 * La classe « js » posee sur <html> est ce qui autorise le CSS a masquer le
 * menu : rien n'est jamais cache a quelqu'un dont le script n'a pas tourne.
 */
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', function () {
    var button = document.querySelector('[data-nav-button]');
    var menu = document.querySelector('[data-nav-menu]');

    if (!button || !menu) {
        return;
    }

    function setOpen(open) {
        menu.classList.toggle('is-open', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
    }

    setOpen(false);

    button.addEventListener('click', function () {
        setOpen(!menu.classList.contains('is-open'));
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
});
