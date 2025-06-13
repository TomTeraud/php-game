<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($title) ? htmlspecialchars($title) : 'My PHP App' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN - THIS IS THE ADDITION -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Your existing custom styles can remain here, though Tailwind will often override them */
        body {
            font-family: 'Inter', sans-serif; /* Prefer Inter if available, fallback to sans-serif */
            /* Removed padding, max-width, margin from body as Tailwind will handle layout via main/div */
        }
        nav {
            /* Tailwind will handle most nav styling, but specific overrides can go here */
            margin-bottom: 1em; /* You can replace this with Tailwind 'mb-4' etc. */
        }
        nav a {
            margin-right: 10px; /* You can replace this with Tailwind 'mr-2.5' or 'px-2' etc. */
        }
        button {
            /* Tailwind classes on buttons will override these */
            margin-top: 0.5em;
        }
    </style>
    <!-- Optionally, you can add Google Fonts if you use 'Inter' -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
