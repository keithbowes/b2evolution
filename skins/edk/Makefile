locales=$(patsubst %/,%,$(patsubst locales/%,%,$(dir $(wildcard locales/*/))))
sources=_403_forbidden.disp.php _404_not_found.disp.php _html_header.inc.php _item_comment.inc.php \
		_item_comment_form.inc.php _sidebar.inc.php index.main.php
XG= ../../_transifex/xg.php

all: generate-pot update-po convert clean

convert:
	$(XG) CWD convert $(locales)

generate-pot:
	$(XG) CWD extract $(sources)

update-po:
	$(XG) CWD merge $(locales)

clean:
	rm -f $(wildcard locales/*/LC_MESSAGES/*~)

