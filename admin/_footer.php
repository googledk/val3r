</main>
</div>
<script>
document.addEventListener('change', function(e){
    const input = e.target.closest('[data-preview-input]');
    if (!input || !input.files || !input.files[0]) return;
    const img = document.querySelector(input.dataset.previewInput);
    if (img) img.src = URL.createObjectURL(input.files[0]);
});
</script>
</body>
</html>
