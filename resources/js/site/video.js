// Click-to-load video: no request to YouTube until the visitor asks for it (privacy-enhanced domain).
document.querySelectorAll('[data-video]').forEach((button) => {
    // CSSOM styling is allowed by the CSP (inline style attributes are not).
    if (button.dataset.poster) button.style.backgroundImage = `url("${button.dataset.poster}")`;

    button.addEventListener('click', () => {
        const frame = document.createElement('iframe');
        frame.className = 'video-frame';
        frame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(button.dataset.video)}?autoplay=1&rel=0`;
        frame.title = button.dataset.title || 'Video';
        frame.allow = 'autoplay; encrypted-media; picture-in-picture';
        frame.allowFullscreen = true;
        button.replaceWith(frame);
        frame.focus();
    });
});
