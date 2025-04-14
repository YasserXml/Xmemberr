@if(session()->has('invoice_url') && request()->has('open_invoice'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.open('{{ session('invoice_url') }}', '_blank');
    });
</script>
@endif