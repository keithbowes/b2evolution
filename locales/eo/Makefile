# Facile aktualigi la tradukajn dosierojn
# Ŝanĝi la variablon XG_PHP al la vojo de la programeto xg.php
XG_PHP ?= $(shell pwd)/../../_transifex/xg.php
XG ?= php -f $(XG_PHP)

ĉio: purigi
	cd ../.. && $(XG) CWD convert eo

update-po ĝisdatigi: ĝisdatigi-mesaĝojn ĝisdatigi-statikan

ĝisdatigi-mesaĝojn:
	cd ../.. && $(XG) CWD merge eo

ĝisdatigi-statikan:
	touch ../static.pot LC_MESSAGES/static.po
	cd LC_MESSAGES && cp -f static.po static.po~
	@msgmerge -Uq LC_MESSAGES/static.po ../static.pot

clean distclean purigi:
	rm -f $(wildcard _global.php  ../eo*.tar.xz)
	rm -f $(wildcard LC_MESSAGES/*.po~)

xz: ĉio
	cd .. && tar c eo | xz -f9 > eo.tar.xz
