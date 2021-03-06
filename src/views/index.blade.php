<?php
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");
?>
        <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PharmIT API Docs</title>
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

        var clientId = false;
        var clientSecret = false;

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

                    $.get( "client", function( data ) {
                      var options;

                      while(data.length) {
                        var d = data.pop();
                        options += '<option client_id="'+d.id+'" client_secret="'+d.secret+'">'+d.name+'</option>';
                      }
                      document.getElementById('clientSelect').innerHTML += options;
                    });

                    $("#userList").on('click', '.tokenMaker', function () {
                        if (clientId === false || clientSecret === false) {
                            window.alert("Please select a client first.");
                            return;
                        }

                        var name = this.innerHTML;
                        var password = '';
                        var email = name.toLowerCase() + "@pharmit.nl";

                        $.post("/api/token", {
                            "grant_type": "password",
                            "client_id": clientId,
                            "client_secret": clientSecret,
                            "username": email,
                            "password": password
                        }, function (data) {
                            var el = $("#inputapiKey")[0];
                            el.value = (data.access_token);

                            document.getElementById("typeHelper").innerHTML = " - Logged in as <u>User</u>";

                            setAPIKey(el)
                        }).fail(function (data) {
                            window.alert('Could not log on as user: ' + name);
                        });
                    });

                    if (window.SwaggerTranslator) {
                        window.SwaggerTranslator.translate();
                    }

                    $('pre code').each(function (i, e) {
                        hljs.highlightBlock(e)
                    });
                },
                onFailure: function (data) {
                    log("Unable to Load SwaggerUI");
                },
                docExpansion: "none",
                apisSorter: "alpha",
                showRequestHeaders: false
            });

            $('#clientButton').click(function () {
              var client_id = $( "#clientSelect option:selected" ).attr('client_id');
              var client_secret = $( "#clientSelect option:selected" ).attr('client_secret');

              if(client_id == null || client_secret == null) {
                window.alert("Please select a client.");
                return;
              }

              $.post( "oauth/client", { grant_type: 'client_credentials', client_id: client_id, client_secret: client_secret}, function( data ) {
                var apiKeyAuth = new SwaggerClient.ApiKeyAuthorization("Authorization", data.access_token, "header");
                window.swaggerUi.api.clientAuthorizations.add("Authorization", apiKeyAuth);
                $('#inputapiKey').val(data.access_token);
              });
            });

            $('#credentialsButton').click(function () {
              var credentials_username = '{{ env('CREDENTIALS_USERNAME', 'blank')}}';
              var credentials_password = '{{ env('CREDENTIALS_PASSWORD', 'blank')}}';

              if(credentials_username == 'blank' || credentials_password == 'blank') {
                window.alert("There are no credentials defined in the environment.");
                return;
              }

              var client_id = $( "#clientSelect option:selected" ).attr('client_id');
              var client_secret = $( "#clientSelect option:selected" ).attr('client_secret');

              if(client_id == null || client_secret == null) {
                window.alert("Please select a client.");
                return;
              }

              $.post( "oauth/credentials", { grant_type: 'password', client_id: client_id, client_secret: client_secret, username: credentials_username, password: credentials_password, scopes: 'createAccount'}, function( data ) {
                var apiKeyAuth = new SwaggerClient.ApiKeyAuthorization("Authorization", data.access_token, "header");
                window.swaggerUi.api.clientAuthorizations.add("Authorization", apiKeyAuth);
                $('#inputapiKey').val(data.access_token);
              });
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
<div id='header' style="position:fixed; left: 0px; right: 0px;">
    <div class="swagger-ui-wrap">
        <a id="logo" href="//pharmit.nl"><span id="typeHelper"></span></a>

        <form id='api_selector'>
            <div class='input'><input onkeyup="setAPIKey(this)" placeholder="Token" id="inputapiKey" name="apiKey"
                                      type="text"/></div>
        </form>
    </div>
</div>

<div id="message-bar" class="swagger-ui-wrap" data-sw-translate>&nbsp;</div>
<div class="swagger-ui-wrap" style="margin-top: 50px">
  <h1>Authentication</h1>
  1. <select id="clientSelect" style="margin-top: 15px"><option selected="" disabled="">-- select --</option></select><br><br>
  2. <button id="clientButton">Client auth</button> or
  <button id="credentialsButton">User auth</button>
  <div class="results"></div>
</div>
<div id="swagger-ui-container" style="margin-top: 40px;" class="swagger-ui-wrap"></div>
</body>
</html>
