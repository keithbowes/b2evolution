# Facile aktualigi la tradukajn dosierojn
# Ŝanĝi la variablon XG_PHP al la vojo de la programeto xg.php
XG_PHP ?= $(shell pwd)/../../_transifex/xg.php
XG ?= php -f $(XG_PHP)

all: purigi
	cd ../.. && $(XG) CWD convert eo

update-po aktualigi ĝisdatigi: update-messages update-static

update-messages:
	cd ../.. && $(XG) CWD merge eo

update-static:
	touch ../static.pot LC_MESSAGES/static.po
	cd LC_MESSAGES && cp -f static.po static.po~
	@msgmerge -Uq LC_MESSAGES/static.po ../static.pot

entoj: all
	sed -i -f entoj.sed _global.php

hsistemo: all
	sed -i -f hsistemo.sed _global.php

xsistemo: all
	sed -i -f xsistemo.sed _global.php

clean distclean purigi:
	rm -f _global.php ../eo*.tar.xz
	rm -f LC_MESSAGES/{messages,static}.po~

xz zip: all
	cd .. && tar c eo | xz -f9 > eo.tar.xz
