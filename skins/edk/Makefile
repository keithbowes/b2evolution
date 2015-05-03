locales=$(patsubst %/,%,$(patsubst templates/locales/%,%,$(dir $(wildcard templates/locales/*/))))
sources=_403_forbidden.disp.php _404_not_found.disp.php _funcs.inc.php \
		_html_header.inc.php _item_comment.inc.php \
		_item_comment_form.inc.php _sidebar.inc.php _user.disp.php

override sources:=$(addprefix templates/,$(sources))

PHP ?= php
XG ?= $(PHP) ../../_transifex/xg.php

all: generate-pot update-po convert clean

convert:
	 $(XG) CWD convert $(locales)

generate-pot:
	 $(XG) CWD extract $(sources)

update-po:
	 $(XG) CWD merge $(locales)

clean:
	rm -f $(wildcard locales/*/LC_MESSAGES/*~)

