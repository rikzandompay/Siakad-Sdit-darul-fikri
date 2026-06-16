@if(config('services.secure_privacy.id'))
    <!-- Secure Privacy CMP -->
    <script type="text/javascript" src="https://app.secureprivacy.ai/script/{{ config('services.secure_privacy.id') }}.js"></script>
    <!-- End Secure Privacy CMP -->
@endif

@if(config('services.gtm.id'))
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ config("services.gtm.id") }}');</script>
    <!-- End Google Tag Manager -->
@endif

@if(config('services.google_analytics.id'))
    <!-- Google Analytics (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ config("services.google_analytics.id") }}');
    </script>
@endif
