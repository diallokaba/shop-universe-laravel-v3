<!-- resources/views/fidelity_card.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Carte de Fidélité</title>
    <style>
        /* Ajoutez du style ici pour personnaliser le PDF */
    </style>
</head>
<body>
    <h1>Carte de Fidélité</h1>
    <p><strong>Nom et prenom:</strong> {{ $pseudo }}</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    @if($qrCodeImageUrl)
    <img src="{{ $qrCodeImageUrl }}" alt="QR Code">
    @else
        <p>Aucune image QR code disponible.</p>
    @endif

</body>
</html>
