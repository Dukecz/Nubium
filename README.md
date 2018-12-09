# Nubium

## How to install on Windows

* Install VirtualBox
* Install Vagrant
* Clone this repository
* append ```192.168.56.101 nubium-sandbox.test``` into hosts file ```C:\Windows\System32\drivers\etc\hosts```
* run ```vagrant up``` in cloned directory

## Known simplifications and limitations

* MySQL database has weak passwords and unnecesarily broad ```nubium``` user permissions
* Usernames can be enumerated during registration
* Frontend is heavily simplified
* vendor folder is versioned because of vagrant
* HTTPS is disabled because of lack of certificates (would be solved with LE)
