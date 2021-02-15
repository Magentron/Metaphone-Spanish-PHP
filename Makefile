#	This metaphone port adapted to the Spanish Language for PHP is authored
#	by Jeroen Derks <jeroen@derks.it> December, 2018
#	(https://github.com/Magentron/Spanish-Metaphone-PHP) and is covered
#	under this copyright:
#
#	Copyright 2018, Jeroen Derks <jeroen@derks.it>.  All rights reserved.
#
#	Redistribution and use in source and binary forms, with or without modification,
#	are permitted provided that the following conditions are met:
#
#	1. Redistributions of source code must retain the above copyright notice, this
#	list of conditions and the following disclaimer.
#	2. Redistributions in binary form must reproduce the above copyright notice, this
#	list of conditions and the following disclaimer in the documentation and/or
#	other materials provided with the distribution.
#
#
#	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
#	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
#	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
#	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
#	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
#	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
#	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
#	ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
#	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
#	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

IODIRNAME=Metaphone-Spanish-PHP
OS:=$(shell uname -s)
NPROCS:=$(shell [ Darwin = $(OS) ] && sysctl -n hw.ncpu || nproc)
PHP_SRC=src
PHP_SRC_TEST=src tests
PHPDOCUMENTOR=phpdocumentor3
PHPUNIT=$(PHP) vendor/bin/phpunit -d xdebug.max_nesting_level=250 -d memory_limit=1024M  --testdox-xml=build/logs/phpunit.xml $(PHPUNIT_EXTRA)
TMP=.tmp

all:	composer test static-analysis phpdox phpdoc-md

$(TMP):
	mkdir -p $(TMP)

clean:
	rm -r $(TMP)

composer:
	composer install --dev

#
#	Testing
#
test-logic:	$(TMP)
	python vendor/amsqr/spanish-metaphone/phonetic_algorithms_es.py > $(TMP)/python.out
	cut -d\  -f 1 $(TMP)/python.out | while read word; do echo "$$word -> `php src/cli/metaphone_es_cli.php \"$$word\"`"; done > $(TMP)/php.out
	diff -bBwU3 $(TMP)/python.out $(TMP)/php.out && echo OK && : make clean

test t:
	time $(PHPUNIT) $(EXTRA)

test-fast fast-test testfast fasttest:
	time $(PHPUNIT) --no-coverage $(EXTRA)

test-func testfunc:
	@[ ! -z "$(FUNC)" ] || (echo "missing FUNC=..."; exit 1)
	make test EXTRA="--filter '/::$(FUNC)\$$\$$/' $(EXTRA)"

test-fast-func test-fastfunc testfast-func testfastfunc test-func-fast test-funcfast testfuncfast:
	@[ ! -z "$(FUNC)" ] || (echo "missing FUNC=..."; exit 1)
	make testfast EXTRA="--filter '/::$(FUNC)\$$\$$/' $(EXTRA)"

test-profiler testprofiler:
	@cwd=`pwd`; if [ -z "$(FUNC)" ]; then \
		make testfast PHP="$(PHP) -d xdebug.profiler_enable=1 -d xdebug.profiler_output_name=cachegrind.out.%p -d xdebug.profiler_output_dir=$$cwd/storage/tmp/xdebug" EXTRA='$(EXTRA)'; \
	 else \
		make testfastfunc PHP="$(PHP) -d xdebug.profiler_enable=1 -d xdebug.profiler_output_name=cachegrind.out.%p -d xdebug.profiler_output_dir=$$cwd/storage/tmp/xdebug" EXTRA='$(EXTRA)' FUNC='$(FUNC)'; \
	 fi

test-stop teststop:
	make test EXTRA="--stop-on-failure $(EXTRA)"

#
#	Lint
#
lint lint-parallel:	
	@make -j4 phplint xmllint

lint-sequential:	phplint xmllint 

bladelint blade-lint lint-blade lintblade:
	@: echo lint - Blade...
	@: nice -20 $(ARTISAN) blade:lint --quiet

jsonlint json-lint lint-json lintjson:
	@echo lint - JSON...
	@find $(SRC) -name '*.json' | nice -20 parallel 'echo {}:; jsonlint -q {}' > .tmp.jsonlint 2>&1;\
		egrep -B1 '^(Error:|\s|\.\.\. )' .tmp.jsonlint | egrep -v ^--; res=$$?; rm -f .tmp.jsonlint; [ 0 != "$$res" ]

phplint php-lint lint-php lintphp:
	@echo lint - PHP...
	@find $(PHP_SRC_TEST) -name '*.php' | nice -20 parallel 'php -l {}' | fgrep -v 'No syntax errors detected' > .tmp.phplint;\
		[ ! -s .tmp.phplint ]; res=$$?; cat .tmp.phplint; rm -f .tmp.phplint; exit $$res

xmllint xml-lint lint-xml lintxml:
	@echo lint - XML...
	@find $(SRC) -name '*.xml' | while read file; do nice -20 xmllint --noout "$$file"; done

#
#	Static code analysis
#
loc:
	@cloc --follow-links $(PHP_SRC_TEST)

static-analysis static-analyzis static analysis analyzis analyse analyze stat anal:	phplint phpcpd phpcs phploc phpmd phpstan

phpcbf:
	vendor/bin/phpcbf --standard=PSR2 -p --parallel=$(NPROCS) -s $(EXTRA) $(PHP_SRC_TEST) 

phpcpd:
	vendor/bin/phpcpd $(EXTRA) $(PHP_SRC_TEST) 

phpcs:	build/logs
	vendor/bin/phpcs --standard=PSR2 -p --parallel=$(NPROCS) --report-xml=build/logs/phpcs.xml -s $(EXTRA) $(PHP_SRC_TEST) 

phploc:	build/logs
	vendor/bin/phploc --log-xml=build/logs/phploc.xml $(EXTRA) $(PHP_SRC_TEST)

phpmd:
	-vendor/bin/phpmd $(PHP_SRC) ansi cleancode,codesize,controversial,design,naming,unusedcode $(EXTRA)

phpmd-xml phpmdx:	build/logs
	-vendor/bin/phpmd $(PHP_SRC) xml cleancode,codesize,controversial,design,naming,unusedcode --report-file build/logs/pmd.xml $(EXTRA)

phpstan:
	vendor/bin/phpstan analyse $(EXTRA) $(PHP_SRC_TEST)

phpdoc-md:
	vendor/bin/phpdoc-md

phpdox:	phpmdx
	vendor/bin/phpdox

build build/logs:
	mkdir -p $@

deploy:	static-analysis test phpdox phpdoc-md
	cp -va build/phpdoc-md/* ../magentron.github.io/$(IODIRNAME)/
	ln -nsf README.md ../magentron.github.io/$(IODIRNAME)/index.md
