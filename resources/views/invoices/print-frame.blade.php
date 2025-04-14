<!DOCTYPE html>
<html>
<head>
    <title>Cetak Invoice</title>
    <script>
    window.onload = function() {
        var iframe = document.getElementById('invoice-frame');
        iframe.onload = function() {
            setTimeout(function() {
                iframe.contentWindow.print();
            }, 1000);
        };
    };
    </script>
</head>
<body>
    <iframe id="invoice-frame" src="{{ route('transaksi.invoice', ['transaksi' => $transaksi->id]) }}" 
            style="width:100%; height:100vh; border:none;"></iframe>
</body>
</html>