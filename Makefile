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

OS:=$(shell uname -s)
NPROCS:=$(shell [ Darwin = $(OS) ] && sysctl -n hw.ncpu || nproc)
PHP_SRC=src
PHP_SRC_TEST=src tests
PHPUNIT=$(PHP) vendor/bin/phpunit -d xdebug.max_nesting_level=250 -d memory_limit=1024M  --testdox-xml=build/logs/phpunit.xml $(PHPUNIT_EXTRA)
TMP=.tmp

all:	composer test static-analysis phpdox

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

#
#	Static code analysis
#
static-analysis static-analyzis static analysis analyzis analyse analyze stat anal:	phplint phpcpd phpcs phploc phpmd phpstan

phpcpd:
	vendor/bin/phpcpd $(EXTRA) $(PHP_SRC_TEST) 

phpcbf:
	vendor/bin/phpcbf --standard=PSR2 -p --parallel=$(NPROCS) -s $(EXTRA) $(PHP_SRC_TEST) 

phpcs:	build/logs
	vendor/bin/phpcs --standard=PSR2 -p --parallel=$(NPROCS) --report-xml=build/logs/phpcs.xml -s $(EXTRA) $(PHP_SRC_TEST) 

phploc:	build/logs
	vendor/bin/phploc --log-xml=build/logs/phploc.xml $(EXTRA) $(PHP_SRC_TEST)

phpmd:	build/logs
	-vendor/bin/phpmd $(PHP_SRC) ansi cleancode,codesize,controversial,design,naming,unusedcode $(EXTRA)

phpmd-xml phpmdx:	build/logs
	-vendor/bin/phpmd $(PHP_SRC) xml cleancode,codesize,controversial,design,naming,unusedcode --report-file build/logs/pmd.xml $(EXTRA)

phpstan:
	vendor/bin/phpstan analyse $(EXTRA) $(PHP_SRC_TEST)

phplint php-lint lint-php lintphp lint:
	@echo lint - PHP...
	@find $(PHP_SRC_TEST) -name '*.php' | nice -20 parallel 'php -l {}' | fgrep -v 'No syntax errors detected' > .tmp.phplint;\
		[ ! -s .tmp.phplint ]; res=$$?; cat .tmp.phplint; rm -f .tmp.phplint; exit $$res

phpdox:	phpmdx
	vendor/bin/phpdox

build build/logs:
	mkdir -p $@
