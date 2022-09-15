<div id="biller-tab" class="mt-2 tab">
    <ul class="nav nav nav-tabs d-print-none" role="tablist">
        <li class="nav-item">
            <a class="nav-link active show" role="tab"
               aria-expanded="true" aria-selected="true">
                <i class="material-icons">credit_card</i>
                Biller business invoice
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <p class="mb-1">
            <strong>Payment status: </strong>
            {html_entity_decode($status|escape:'html':'UTF-8')}
        </p>
        <p class="mb-1">
            <strong>Payment link</strong>
        </p>

        <div class="form-group row" style="margin-left: inherit">

            <input type="text"
                   class="col-md-6 form-control "
                   disabled>

            <button class="btn btn-sm btn-outline-secondary">
                Copy
            </button>
        </div>

        <div class="form-group row-buttons ">
            <button class="btn btn-sm btn-outline-secondary">
                Cancel
            </button>
            <button class="btn btn-sm btn-outline-secondary">
                Capture
            </button>
        </div>
    </div>

</div>

<script>
    const child = document.getElementById('biller-tab');
    child.parentElement.className = '';
</script>