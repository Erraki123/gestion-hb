<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Document PDF</title>

    <style>
        @page {
            margin: 25px;
            size: A4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #222;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .pdf-container {
            width: 100%;
            padding: 10px;
        }

        h1 {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 25px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 9px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-2xl {
            font-size: 22px;
        }

        .text-sm {
            font-size: 12px;
        }

        .mt-6 {
            margin-top: 24px;
        }

        .w-full {
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="pdf-container">
        {{ $slot }}
    </div>
</body>
</html>