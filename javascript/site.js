/* Carrossel F-L-O-R-I-F-Y: toque/clique/Enter expande o slide (funciona sem hover) */
document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.expand-carousel .slide');
    if (!slides.length) return;

    const ativar = (slide) => {
        const jaAtivo = slide.classList.contains('ativo');
        slides.forEach((s) => s.classList.remove('ativo'));
        if (!jaAtivo) slide.classList.add('ativo');
    };

    slides.forEach((slide) => {
        slide.addEventListener('click', () => ativar(slide));
        slide.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                ativar(slide);
            }
        });
    });
});
