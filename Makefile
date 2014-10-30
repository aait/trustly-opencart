VERSION:=$$(head -n20 catalog/controller/payment/trustly.php | grep -w _module_version | cut -f2 -d\')
PACKAGE=trustly-opencart
ROOT=$(PWD)
FILES=$$(find admin/ catalog/ system/ trustly-notification.php -type f \! -name \*.git \! -name README.md \! -name LICENCE)
DESTDIR?=$(HOME)/opencart-1.5.6.4/upload
BUILDDIR:=$(PACKAGE)-$(VERSION)-build
TARGET:=$(PACKAGE)-$(VERSION).zip


all: zip

.PHONY: install
install:
	for file in $(FILES); do  \
		mkdir -p "$(DESTDIR)/$$(dirname $$file)"; \
		ln -sf "$(ROOT)/$$file" "$(DESTDIR)/$$(dirname $$file)"; \
	done


.PHONY: clean
clean:
	rm -rf "$(BUILDDIR)"

.PHONY: zip
zip: clean
	mkdir -p "$(BUILDDIR)"
	tar -cf - $(FILES) | (cd "$(BUILDDIR)"; tar -xf -)
	(cd "$(BUILDDIR)"; zip -r ../"$(TARGET)" *)
	@unzip -v "$(TARGET)"
	@ls -la "$(TARGET)"


