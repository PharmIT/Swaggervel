<?php
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");
?>
        <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Swagger UI</title>
    <link rel="icon" type="image/png" href="vendor/swaggervel/images/favicon-32x32.png" sizes="32x32"/>
    <link rel="icon" type="image/png" href="vendor/swaggervel/images/favicon-16x16.png" sizes="16x16"/>
    <link href='vendor/swaggervel/css/typography.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='vendor/swaggervel/css/reset.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='vendor/swaggervel/css/screen.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='vendor/swaggervel/css/reset.css' media='print' rel='stylesheet' type='text/css'/>
    <link href='vendor/swaggervel/css/print.css' media='print' rel='stylesheet' type='text/css'/>
    <script src='vendor/swaggervel/lib/jquery-1.8.0.min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/jquery.slideto.min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/jquery.wiggle.min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/jquery.ba-bbq.min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/handlebars-2.0.0.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/underscore-min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/backbone-min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/swagger-ui.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/highlight.7.3.pack.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/marked.js' type='text/javascript'></script>

    <script src='vendor/swaggervel/lib/swagger-oauth.js' type='text/javascript'></script>

    <script type="text/javascript">

        function log() {
            if ('console' in window) {
                console.log.apply(console, arguments);
            }
        }

        $(function () {
            var url = window.location.search.match(/url=([^&]+)/);
            if (url && url.length > 1) {
                url = decodeURIComponent(url[1]);
            } else {
                url = "{!! $urlToDocs !!}";
            }

            // Pre load translate...
            if (window.SwaggerTranslator) {
                window.SwaggerTranslator.translate();
            }
            window.swaggerUi = new SwaggerUi({
                url: url,
                dom_id: "swagger-ui-container",
                supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
                onComplete: function (swaggerApi, swaggerUi) {

                    log("Loaded SwaggerUI");
                    $(".tokenMaker").click(function () {
                        var name = this.innerHTML;
                        var password = name;
                        var email = name.toLowerCase() + "@eventix.nl";
                        var from = ['a', 'e', 'i', 'o', 'h', 't'];
                        var to = ['@', '3', '1', '0', '4', '7'];

                        for (var i in from) {
                            password = password.replace(from[i], to[i]);
                        }

                        $.post("/api/token", {
                            "grant_type": "password",
                            "client_id": "testclient",
                            "client_secret": "testsecret",
                            "username": email,
                            "password": password
                        }, function (data) {
                            var el = $("#inputapiKey")[0];
                            el.value = (data.access_token);
                            setAPIKey(el)
                        }).fail(function (data) {
                            window.alert('Could not log on as user: ' + name);
                        });
                    });

                    $(".clientMaker").click(function () {

                        var client = this.innerHTML;
                        console.log(client);
                        $.get("/api/client/" + client, function (data) {
                            if (!data.id && !data.secret) {
                                window.alert('Could not log on as client: ' + client);
                                return;
                            }

                            var client = data.id;
                            var secret = data.secret;

                            $.post("/api/token", {
                                "grant_type": "client_credentials",
                                "client_id": client,
                                "client_secret": secret,
                            }, function (data) {
                                var el = $("#inputapiKey")[0];
                                el.value = (data.access_token);
                                setAPIKey(el)
                            });
                        }).fail(function (data) {
                            window.alert('Could not log on as client: ' + client);
                        });

                    });


                    if (typeof initOAuth == "function") {
                        initOAuth({
                            clientId: "{!! $clientId !!}" || "my-client-id",
                            clientSecret: "{!! $clientSecret !!}" || "_",
                            realm: "{!! $realm !!}" || "_",
                            appName: "{!! $appName !!}" || "_",
                            scopeSeparator: ","
                        });

                        window.oAuthRedirectUrl = "{{ url('vendor/swaggervel/o2c.html') }}";
                        $('#clientId').html("{!! $clientId !!}" || "my-client-id");
                        $('#redirectUrl').html(window.oAuthRedirectUrl);
                    }

                    if (window.SwaggerTranslator) {
                        window.SwaggerTranslator.translate();
                    }

                    $('pre code').each(function (i, e) {
                        hljs.highlightBlock(e)
                    });

                    addApiKeyAuthorization();
                },
                onFailure: function (data) {
                    log("Unable to Load SwaggerUI");
                },
                docExpansion: "none",
                apisSorter: "alpha",
                showRequestHeaders: false
            });

            $('#init-oauth').click(function () {
                if (typeof initOAuth == "function") {
                    initOAuth({
                        clientId: $('#input_clientId').val() || "my-client-id",
                        clientSecret: $('#input_clientSecret').val() || "_",
                        realm: $('#input_realm').val() || "_",
                        appName: $('#input_appName').val() || "_",
                        scopeSeparator: "+"
                    });
                }
            });

            window.swaggerUi.load();

        });

        function setAPIKey(el) {
            if (!el)
                return;

            var value = el.value;

            if (value.length == 40) {
                var apiKeyAuth = new SwaggerClient.ApiKeyAuthorization("Authorization", value, "header");
                window.swaggerUi.api.clientAuthorizations.add("Authorization", apiKeyAuth);
            }
        }
    </script>
</head>

<body class="swagger-section">
<div id='header'>
    <div class="swagger-ui-wrap">
        <a id="logo" href="http://swagger.io">swagger</a>

        <form id='api_selector'>
            <div class='input'><input onkeyup="setAPIKey(this)" placeholder="Token" id="inputapiKey" name="apiKey"
                                      type="text"/></div>
        </form>
    </div>
</div>

<div id="message-bar" class="swagger-ui-wrap" data-sw-translate>&nbsp;</div>
<div id="swagger-ui-container" class="swagger-ui-wrap"></div>
</body>
</html>
