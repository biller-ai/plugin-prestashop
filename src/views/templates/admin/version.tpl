<p id='version' class="biller-version">
    <script>
        const child = document.getElementById('version');
        child.parentElement.className = '';
    </script>
    v{html_entity_decode($version|escape:'html':'UTF-8')}
</p>