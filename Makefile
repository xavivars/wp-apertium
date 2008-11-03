VERSION=0.8

FILES=wp_apertium.php css js inc README COPYING AUTHORS
all: dist

dist: $(FILES) Makefile
	rm -Rf wp-apertium
	mkdir wp-apertium
	cp -r $(FILES) Makefile wp-apertium
	tar cvf wp-apertium-$(VERSION).tar wp-apertium --exclude *.svn*
	rm -Rf wp-apertium
	gzip -9 -f wp-apertium-$(VERSION).tar
	mv wp-apertium-$(VERSION).tar.gz wp-apertium-$(VERSION).tgz

zip: $(FILES) Makefile
	rm -Rf wp-apertium
	mkdir wp-apertium
	cp -r $(FILES) Makefile wp-apertium
	zip -r wp-apertium-$(VERSION).zip wp-apertium/* -x *.svn*
	rm -Rf wp-apertium

	
