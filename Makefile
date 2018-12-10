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

TMP=.tmp

all:
	@echo no default target...

test:	$(TMP)
	python phonetic_algorithms_es.py > $(TMP)/python.out
	php phonetic_algorithms_es.php > $(TMP)/php.out
	diff -bBwU3 $(TMP)/python.out $(TMP)/php.out && echo OK && : make clean

$(TMP):
	mkdir -p $(TMP)

clean:
	rm -r $(TMP)
