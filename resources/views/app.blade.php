<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Post Reviewer') }} - Social Media Post Approval Made Simple</title>
    <meta name="description" content="Streamline your social media workflow. Create post mockups, share with clients, and get approvals faster. No more back-and-forth emails or confusing spreadsheets.">
    <meta name="keywords" content="social media approval, post mockup, client approval, social media workflow, content approval, marketing collaboration">
    <meta name="author" content="Post Reviewer">
    <link rel="canonical" href="https://postreviewer.com/">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/post-reviewer-icon-512.png">
    <link rel="apple-touch-icon" href="/images/post-reviewer-icon-512.png">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://postreviewer.com/">
    <meta property="og:title" content="Post Reviewer - Social Media Post Approval Made Simple">
    <meta property="og:description" content="Streamline your social media workflow. Create post mockups, share with clients, and get approvals faster.">
    <meta property="og:image" content="https://postreviewer.com/images/post-reviewer-cover-og-1200x630.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Post Reviewer">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://postreviewer.com/">
    <meta name="twitter:title" content="Post Reviewer - Social Media Post Approval Made Simple">
    <meta name="twitter:description" content="Streamline your social media workflow. Create post mockups, share with clients, and get approvals faster.">
    <meta name="twitter:image" content="https://postreviewer.com/images/post-reviewer-cover-twitter-1500x500.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:600,700&display=swap" rel="stylesheet" />

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "Post Reviewer",
        "description": "Streamline your social media workflow. Create post mockups, share with clients, and get approvals faster. No more back-and-forth emails or confusing spreadsheets.",
        "url": "https://postreviewer.com",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "5",
            "ratingCount": "1"
        },
        "author": {
            "@type": "Organization",
            "name": "Post Reviewer",
            "url": "https://postreviewer.com",
            "logo": "https://postreviewer.com/images/post-reviewer-icon-512.png",
            "sameAs": [
                "https://www.facebook.com/getpostreviewer"
            ]
        }
    }
    </script>

    <!-- Dark mode initialization (prevents flash) -->
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if(app()->environment('local'))
    <!-- Tailwind Play CDN for DevTools experimentation -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'selector',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22',
                        }
                    }
                }
            }
        }
    </script>
    @endif
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
