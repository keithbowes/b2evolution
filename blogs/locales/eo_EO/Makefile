# Facile aktualigi la tradukajn dosierojn
# Ŝanĝi la variablon XG_PHP al la vojo de la programeto xg.php
XG_PHP ?= $(shell pwd)/../../../gettext/xg.php
XG ?= php -f $(XG_PHP)

all: purigi
	cd ../.. && $(XG) CWD convert eo_EO

update-po aktualigi ĝisdatigi: update-messages update-static

update-messages:
	cd ../.. && $(XG) CWD merge eo_EO

update-static:
	cd LC_MESSAGES && cp -f static.po static.po~
	msgmerge -Uq LC_MESSAGES/static.po ../../../gettext/transifex/static.pot

entoj: all
	sed -i -f entoj.sed _global.php

hsistemo: all
	sed -i -f hsistemo.sed _global.php

xsistemo: all
	sed -i -f xsistemo.sed _global.php

clean distclean purigi:
	rm -f _global.php ../eo-EO.{tar.{bz2,gz,xz},zip}
	rm -f LC_MESSAGES/{messages,static}.po~

deprecated:
	@echo "AVERTO: Ĉi tiu celo estas malaperonta"

zip: all deprecated
	cd .. && zip -9 -r eo-EO.zip eo_EO

gz: all deprecated
	cd .. && tar c eo_EO | gzip -f9 > eo-EO.tar.gz

bz bz2: all deprecated
	cd .. && tar c eo_EO | bzip2 -f9 > eo-EO.tar.bz2

xz: all
	cd .. && tar c eo_EO | xz -f9 > eo-EO.tar.xz
