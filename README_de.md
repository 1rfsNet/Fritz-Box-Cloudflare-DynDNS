# Fritz!Box DynDNS Skript für Cloudflare
For English version, please read the English readme file.

Dieses PHP Script ist für die Nutzung mit einer Fritz!Box ausgelegt, funktioniert aber sehr wahrscheinlich auch mit anderen Routern und Geräten, die die Möglichkeit bieten eine benutzerdefinierte URL aufzurufen. Die Fritz!Box ruft das Script selbstständig auf, sobald die IP-Adresse geändert wurde.

# Voraussetzungen
Es wird lediglich ein Webserver, ein Cloudflare Account (Free/Paid) und eine Domain, die bei Cloudflare registriert ist, benötigt. Der Webserver benötigt lediglich PHP und muss natürlich über das Internet erreichbar sein. Ggf. funktioniert es auch mit einem Server im eigenen Netzwerk, das wurde allerdings nicht getestet.

# Funktionen
- Übermittlung der aktuellen IP-Adresse der Fritzbox an Cloudflare (per API)
- Es kann eine unlimitierte Anzahl an Records mit einem Aufruf erstellt werden
- Das Script funktioniert auch, wenn der Webserver hinter einem Cloudflare Proxy steht
- Unterstützung aller Record-Typen, die eine IP nutzen (A, AAAA, MX, etc.)
- Automatisch Erkennung von IPv4 und IPv6*
- Detailiertes Log (optional aktivierbar)
- Der Record kann automatisch durch Cloudflare proxifiziert werden (sinnvoll je nach Anwendungsfall - z.B. Webserver)

* Aus technischen Gründen ist es leider nicht möglich einen Record für IPv4 UND IPv6 zu setzen, da die Fritzbox beim Aufruf des Scripts lediglich die IPv4 bzw. IPv6 Adresse übermittelt, aber nicht beide.

# Installation
Klone das Repository auf deinen Webserver. 

An dem Script musst du nichts verändern. Alle Einstellungen erfolgen in deiner Fritz!Box. Stelle lediglich sicher, dass das Skript für die Fritz!Box erreichbar ist (ohne Authentifizierung).

Zunächst benötigst du einen API-Token von Cloudflare. Rufe dazu https://dash.cloudflare.com/profile/api-tokens auf und klicke auf "Create Token".
Im folgenden Schritt kannst du jetzt verschiedene Templates auswählen. Am besten verwendest du "Edit zone DNS". 
Zunächst musst du die "Permissions" konfigurieren. Das Skript benötigt nur Zugriff auf "Zone.Zone" (Read) und "Zone.DNS" (Edit).
Bei den "Zone Resources" kannst du entweder die gewünschte Zone oder alle Zonen auswählen. Optional kannst du auch noch die IP deines Webservers auf die Whitelist setzen, um die Sicherheit zu verbessern.
Die Option TTL solltest du so belassen, ansonsten funktioniert der Token und damit das Skript irgendwann nicht mehr.
Jetzt musst du nur noch bestätigen und bekommst den Token angezeigt.

Wechsel jetzt auf die Weboberfläche deiner Fritz!Box: Internet -> Freigaben -> DynDNS
Aktiviere den DynDNS Dienst und wähle "Benutzerdefiniert" als Anbieter aus. 
In der Update-URL gibst du nun "https://deine-domain.com/fritzbox_dyndns.php?cf_key=<pass>&domain=<domain>" ein.
Folgende URL-Paramter stehen zur Verfügung und können bei Bedarf der URL hinzugefügt werden:
- Log: Das Script schreibt ein detailiertes Log in das Verzeichnis der PHP-Datei. Füge dafür "&log=true" an das Ende der URL an.
- Proxy: Das Script aktiviert den Proxy-Modus von Cloudflare für die DNS-Records. Füge dafür "&proxy=true" an das Ende der URL an.
Im Folgenden kannst du nun den Domainnamen eingeben, also den gewünschten DNS-Record. Das Script kann auch mehrere DNS-Records (auch mit verschiedenen Domains) verarbeiten. Dazu musst du die Records lediglich mit einem Simikolon trennen (z.B. fritzbox.deine-domain.com;nas.deine-domain.com;fritzbox.example.com).
Die Eingabe eines Benutzernamens ist leider ein Pflichtfeld, wird vom Script aber nicht benötigt. Trage dort einfach "null" ein, der Wert wird nicht berücksichtigt.
Abschließend musst du noch den bei Cloudflare generierten API-Token als Kennwort eintragen und "Übernehmen" klicken. 
Die Fritz!Box sollte sich nun in Kürze melden.
    
Hinweis: Es sollte kein Problem sein, wenn das Skript öffentlich erreichbar ist, da du den Cloudflare API-Token bei jedem Aufruf übergeben musst. 
    
# Known Bugs und FAQ
## Die Fritz!Box zeigt im Log regelmäßig die Fehlermeldung "DynDNS-Fehler: Der angegebene Domainname kann trotz erfolgreicher Aktualisierung nicht aufgelöst werden." an.
Die Ursache für den Fehler ist mir nicht bekannt. Der Fehler tritt auch auf, wenn man nur einen Domainnamen angegeben hat. Theoretisch löst die Fritz!Box den angegebenen Namen auf, bekommt die IP und gleicht diese mit der eigenen IP ab. Obwohl die IP übereinstimmt, wird der Fehler trotzdem angezeigt. Die Fehlermeldung hat aber keinerlei Einfluss auf die Funktion.

## Die Fritz!Box zeigt im Log die Fehlermeldung "DynDNS-Fehler: Der DynDNS-Anbieter meldet 401 - Unauthorized" o.ä. an.
Im Falle der o.g. Fehlermeldung ist vermutlich einen Authentifizierung über die .htaccess Datei aktiv. Steht in der Meldung ein anderer HTTP-Fehler, liegt ebenfalls ein Fehler beim Webserver vor.

## Die IP-Adresse des DNS-Records stimmt nicht.
Ein Grund ist möglicherweise, dass dein Webserver hinter einem Proxy läuft, der die IP verfälscht. Das ist z.B. bei Cloudflare der Fall, wenn der Proxy aktiv ist (die IP-Adresse ist dann nicht mehr deine, sonder eine von Cloudflare). Der Cloudflare Proxy wurde in das Skript implementiert und funktioniert somit auch, wenn sich der Webserver hinter dem Proxy befindet. Bei ähnlichen Diensten, muss vermutlich eine Anpassung am Skript vorgenommen werden. Erstelle dazu ein Issue und ich schaue es mir an.

## Es passiert nichts!?!
Aktiviere zunächst das Log ("&log=true" an die URL anhängen) und schaue dir das Log an. Wenn du nichts finden kannst, erstelle ein Issue mit dem Inhalt des Logfiles.

## Kann das Script DNS-Records löschen?
Nein! Das Skript löscht keine DNS-Records. Das Skript prüft, ob der angegebene DNS-Record schon existiert und erstellt ggf. einen. Ansonsten wird der Records nur geupdated, aber niemals gelöscht.

## Welche DNS-Record-Typen werden unterstützt?
Es werden alle Typen von Records unterstützt, die eine IP verwenden, also z.B. A, AAAA, MX, etc. Das Skript kann jedoch lediglich A und AAAA Records anlegen. Wenn du beispielsweise auch einen MX-Record benötigst, musst du diesen selbst in deinem Cloudflare Account anlegen. Die regelmäßigen Updates werden dann von dem Skript durchgeführt.

## Wird auch IPv6 unterstützt?
Jein! Deine Fritz!Box ruft das Skript nur mit einer IP auf (also entweder IPv4 oder IPv6). Das kannst du selbst nur bedingt beeinflussen. Steht dir Dual-Stack mit IPv4 und IPv6 zur Verfügung, kommt es darauf an, ob du den Webserver mit IPv4 oder IPv6 ansprichst. Leider fehlt mir die Möglichkeit es zu testen, aber sehr wahrscheinlich muss dein Webserver IPv6 unterstützen, wenn du Dual-Stack-Lite hast. 
Grundsätzlich kann das Skript aber erkennen, ob du einen IPv4 oder IPv6 Adresse hast und erstellt dementsprechend einen A- oder AAAA-Record.

# Geplante Funktionen
- Ermittlung der IPv4 bzw. IPv6 Adresse über MyFritz, sodass parallel für IPv4 und Ipv6 jeweils ein DNS-Record erstellt werden kann
- Optionale konfigurierbare E-Mail Benachrichtigung (z.B. bei Fehlern oder auf Wunsch bei jeder IP-Änderung)