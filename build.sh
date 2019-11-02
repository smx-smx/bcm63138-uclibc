#!/bin/bash
##
## Copyright(C) 2019 Stefano Moioli <smxdev4@gmail.com>
##

case "$1" in
	clean)
		make clean
		;;
	install)
		DESTDIR=$PWD/out make install
		;;
	check)
		# Original libraries dumped from router
		if [ -z $ORIGLIBS ]; then
			ORIGLIBS=../../origlib
		fi
		[ -d out/cmp ] && rm -r out/cmp
		mkdir out/cmp

		CROSS="arm-buildroot-linux-uclibcgnueabi-"
		getSyms(){
			local lib="$1"
			${CROSS}readelf -D -s "${lib}" | tail -n +4 | rev | cut -d ' ' -f1 | rev | sort | uniq
		}
		for lib in out/lib/*.so; do
			libFile="$(basename "${lib}")"
			outA="out/cmp/${libFile}.a.syms"
			outB="out/cmp/${libFile}.b.syms"
			getSyms "${ORIGLIBS}/${libFile}" > ${outA}
			getSyms "${lib}" > ${outB}

			echo " == ${libFile} =="
			diff -u ${outA} ${outB}
		done
		;;
	*)
		ARCH=arm \
		CROSS_COMPILE=arm-buildroot-linux-uclibcgnueabi- \
		UCLIBC_EXTRA_CFLAGS="-marm"
		PREFIX=$PWD/out \
		make -j`nproc` | tee make.log
		"$0" install
		;;
esac
