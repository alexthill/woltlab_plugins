<script data-relocate="true">
addEventListener('DOMContentLoaded', () => {
    const bias = (Math.random() - 0.5) * 10000;
    const lang = document.documentElement.lang;
    const el = document.createElement('span');
    const styleChanger = document.querySelector('footer .styleChanger');
    if (styleChanger) {
        el.style.paddingRight = '10px';
        styleChanger.prepend(el);
        updateTime();
    } else {
        el.className = 'styleChanger';
        const footer = document.querySelector('footer .boxContainer');
        if (footer) {
            footer.parentNode.insertBefore(el, footer);
            updateTime();
        }
    }
    function updateTime() {
        const date = new Date(TIME_NOW * 1000 + performance.now() + bias);
        el.textContent = date.toLocaleDateString(lang) + ' ' + date.toLocaleTimeString(lang);
        setTimeout(updateTime, 1000 - (performance.now() % 1000));
    }
});
</script>
