<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новая заявка</title>
</head>

<body>
    <h3>Новая заявка с сайта</h3>
    <p><strong>Имя:</strong> {{ $name }}</p>
    <p><strong>Телеграм:</strong> {{ $telegram }}</p>
    <p><strong>Телефон:</strong> {{ $phone }}</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Услуга:</strong> {{ $service }}</p>
    @if ($customService !== 'N/A')
        <p><strong>Индивидуальная услуга:</strong> {{ $customService }}</p>
    @endif
</body>

</html>
