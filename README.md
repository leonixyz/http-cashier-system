# Cashier System

POC for a cashier system running on a webserver.

## Requirements

* Arch Linux ([netctl](https://wiki.archlinux.org/index.php/netctl), systemd)
* Apache (apache)
* PHP (php, php-apache, php-sqlite)
* [hostapd](https://wiki.archlinux.org/index.php/Software_access_point)
* [dhcpd](https://wiki.archlinux.org/index.php/dhcpd)
* [ESC/P thermal printer on /dev/usb/lp0](http://vi.raptor.ebaydesc.com/ws/eBayISAPI.dll?item=382662737478)
* a wireless wlan0 interface

## Setup

1. `# cp -r ./etc/* /etc`
2. `# cp ./src/* /srv/http`
3. `# chown http:http /srv/http/*`
4. `# usermod -G lp http`
5. `# netctl enable wlan0`
6. `# systemctl enable hostapd dhcpd4@wlan0 httpd`
7. `# systemctl start httpd hostapd dhcpd4@wlan0`

## Tweaks

Products are stored into **./app.sqlite3** in table *products*. Run `# sqlite3 /srv/http/app.sqlite3` and update *products* table according to your needs.

You can also delete **./app.sqlite3**, then point your browser to your host on "/admin.php". This will recreate an empty **app.sqlite3**. Next, you have to re-populate table *products*.

## TODO

* ESC/P allows the printer to send a signal for opening the cash drawer, provided that the printer is connected to a cash drawer with a standard RJ11 cable.
