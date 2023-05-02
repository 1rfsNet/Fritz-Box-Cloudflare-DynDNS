# Fritz!Box DynDNS Skript für Cloudflare
For English version, please read the English readme file.

Dieses PHP Script ist für die Nutzung mit einer Fritz!Box ausgelegt, funktioniert aber möglicherweise auch mit anderen Routern und Geräten, die die Möglichkeit bieten eine benutzerdefinierte URL aufzurufen und die IPv4 und IPv6 Adresse übergeben können. Die Fritz!Box ruft das Script selbstständig auf, sobald die IP-Adresse geändert wurde. Dabei werden die IP-Adressen von der Fritz!Box an das Script übergeben.

# Voraussetzungen
Es wird lediglich ein Webserver, ein Cloudflare Account (Free/Paid) und eine Domain, die bei Cloudflare registriert ist, benötigt. Der Webserver benötigt lediglich PHP, PHP-Curl und muss natürlich über das Internet erreichbar sein. Ggf. funktioniert es auch mit einem Server im eigenen Netzwerk, das wurde bisher allerdings nicht getestet.

# Funktionen
- Übermittlung der aktuellen IP-Adresse der Fritzbox an Cloudflare (per API)
- Es kann eine unlimitierte Anzahl an Records mit einem Aufruf erstellt werden
- Das Script funktioniert auch, wenn der Webserver hinter einem Cloudflare Proxy steht
- Unterstützung aller Record-Typen, die eine IP nutzen (A, AAAA, MX, etc.)
- Automatisch Erkennung von IPv4 und IPv6
- Detailiertes Log (optional aktivierbar)
- Der Record kann automatisch durch Cloudflare proxifiziert werden (sinnvoll je nach Anwendungsfall - z.B. Webserver)

# Installation
Klone das Repository auf deinen Webserver.

An dem Script musst du nichts verändern. Alle Einstellungen erfolgen in deiner Fritz!Box. Stelle lediglich sicher, dass das Skript für die Fritz!Box erreichbar ist (ohne Authentifizierung).

Zunächst benötigst du einen API-Token von Cloudflare. Rufe dazu https://dash.cloudflare.com/profile/api-tokens auf und klicke auf "Create Token".
Im folgenden Schritt kannst du jetzt verschiedene Templates auswählen. Am besten verwendest du "Edit zone DNS".
Zunächst musst du die "Permissions" konfigurieren. Das Skript benötigt nur Zugriff auf "Zone.Zone" (Read) und "Zone.DNS" (Edit).
Bei den "Zone Resources" kannst du entweder die gewünschte Zone oder alle Zonen auswählen. Optional kannst du auch noch die IP deines Webservers auf die Whitelist setzen, um die Sicherheit zu verbessern. Solltest du das tun, vergiss nicht IPv4 und IPv6 freizugeben, sollte dein Webserver beides können.
Die Option TTL solltest du so belassen, ansonsten funktioniert der Token und damit das Skript irgendwann nicht mehr.
Jetzt musst du nur noch bestätigen und bekommst den Token angezeigt.

Wechsel jetzt auf die Weboberfläche deiner Fritz!Box: Internet -> Freigaben -> DynDNS
Aktiviere den DynDNS Dienst und wähle "Benutzerdefiniert" als Anbieter aus.
In der Update-URL gibst du nun folgendes ein:
```
https://deine-domain.com/fritzbox_dyndns.php?cf_key=<pass>&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>
```
Folgende URL-Paramter stehen zur Verfügung und können bei Bedarf der URL hinzugefügt werden:
- Log: Das Script schreibt ein detailiertes Log in das Verzeichnis der PHP-Datei. Füge dafür "&log=true" an das Ende der URL an.
- Proxy: Das Script aktiviert den Proxy-Modus von Cloudflare für die DNS-Records. Füge dafür "&proxy=true" an das Ende der URL an.
Im Folgenden kannst du nun den Domainnamen eingeben, also den gewünschten DNS-Record. Das Script kann auch mehrere DNS-Records (auch mit verschiedenen Domains) verarbeiten. Dazu musst du die Records lediglich mit einem Simikolon trennen (z.B. fritzbox.deine-domain.com;nas.deine-domain.com;fritzbox.example.com).
Die Eingabe eines Benutzernamens ist leider ein Pflichtfeld, wird vom Script aber nicht benötigt. Trage dort einfach "null" ein, der Wert wird nicht berücksichtigt.
Abschließend musst du noch den bei Cloudflare generierten API-Token als Kennwort eintragen und "Übernehmen" klicken.
Die Fritz!Box sollte sich nun in Kürze melden.

Hinweis: Es sollte kein Problem sein, wenn das Skript öffentlich erreichbar ist, da du den Cloudflare API-Token bei jedem Aufruf übergeben musst.

# Known Bugs und FAQ

## Die Fritz!Box zeigt im Log die Fehlermeldung "DynDNS-Fehler: Der DynDNS-Anbieter meldet 401 - Unauthorized" o.ä. an.
Im Falle der o.g. Fehlermeldung ist vermutlich einen Authentifizierung über die .htaccess Datei aktiv. Steht in der Meldung ein anderer HTTP-Fehler, liegt ebenfalls ein Fehler beim Webserver vor.

## Im Log wird eine der folgenden Fehlermeldungen angezeigt: "IPv4 not available or invalid, ignoring" oder "IPv6 not available or invalid, ignoring"
Es ist möglich, dass eine der Meldungen im Log angezeigt wird, obwohl IPv4 bzw. IPv6 verfügbar ist. Die Ursache ist, dass die Fritz!Box teilweise das Script aufruft, bevor eine IPv4 oder IPv6 Adresse vergeben wurde. Somit fehlt dem Script die IPv4 oder IPv6 Adresse. Das Script wird von der Fritz!Box dann später erneut aufgerufen und die Adresse nachträglich geändert.

## Im Log wird "Neither IPv4 nor IPv6 available. Probably the parameters are missing in the update URL. Please note that the update URL has changed with the script version 2.0. You have to change this setting in your Fritz!Box." angezeigt
Nach dem Update auf Version 2.0 muss die Update-URL in deiner Fritz!Box geändert werden (s. Installation). Solltest du das bereits gemacht haben, ist es möglich, dass die Fritz!Box das Script aufgerufen hat, bevor eine IPv4 und IPv6 Adresse zur Verfügung standen. Du musst nichts tun, sobald die IPs zur Verfügung stehen, wird die Fritz!Box das Script erneut aufrufen.

## Die IP-Adresse des DNS-Records stimmt nicht.
Die Fritz!Box übergibt die IPv4 und die IPv6 Adresse selbstständig, diese können also eigentlich nicht falsch sein. Stimmt die IP-Adresse dennoch nicht mehr solltest du testweise das Log aktivieren. Möglicherweise hat sich in deinem Setup irgendwas verändert.
Eine andere Ursache könnte die Umstellung deines Anschlusses sein. Wurdest du beispielsweise von Dual-Stack auf DS-Lite umgestellt, wird der A-Record (IPv4) nicht gelöscht, aber auch nicht mehr geupdated, da du keine öffentliche IPv4 Adresse mehr hast. Die Folge können veraltete Records sein. In einem solchen Fall musst du leider selbst aktiv werden und die veralteten Records manuell löschen. Das gleiche gilt natürlich auch für IPv6.

## Es passiert nichts!?!
Aktiviere zunächst das Log ("&log=true" an die URL anhängen) und schaue dir das Log an. Wenn du nichts finden kannst, erstelle ein Issue mit dem Inhalt des Logfiles.

## Kann das Script DNS-Records löschen?
Nein! Das Skript löscht keine DNS-Records. Das Skript prüft, ob der angegebene DNS-Record schon existiert und erstellt ggf. einen. Ansonsten wird der Records nur geupdated, aber niemals gelöscht. Bitte berücksichtige das, falls IPv4 oder IPv6 bei dir abgeschaltet wird (s. "Die IP-Adresse des DNS-Records stimmt nicht.")

## Welche DNS-Record-Typen werden unterstützt?
Es werden alle Typen von Records unterstützt, die eine IP verwenden, also z.B. A, AAAA, MX, etc. Das Skript kann jedoch lediglich A und AAAA Records anlegen. Wenn du beispielsweise auch einen MX-Record benötigst, musst du diesen selbst in deinem Cloudflare Account anlegen. Die regelmäßigen Updates werden dann von dem Skript durchgeführt.

## Wird auch IPv6 unterstützt?
Ja, die Fritz!Box übergibt auch die IPv6 Adresse. Das Script erkennt automatisch ob eine IPv6 Adresse vorhanden ist oder nicht. Falls ja, wird für diese ein AAAA-Record erstellt. Falls nicht, wird IPv6 komplett übersprungen.

# Geplante Funktionen
- Optionale konfigurierbare E-Mail Benachrichtigung (z.B. bei Fehlern oder auf Wunsch bei jeder IP-Änderung)
- Löschen von Records zur Verhinderung veralteter Records (nur im Fall, dass ein Protokoll nicht mehr zur Verfügung steht: z.B. Umstellung von DS auf DS-Lite)
