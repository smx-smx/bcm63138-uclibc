/* vi: set sw=4 ts=4: */
/*
 * sync_file_range() for uClibc
 *
 * Copyright (C) 2008 Bernhard Reutner-Fischer <uclibc@uclibc.org>
 *
 * Licensed under the LGPL v2.1 or later, see the file COPYING.LIB in this tarball.
 */

#include <sys/syscall.h>
#if defined __UCLIBC_HAS_LFS__ && defined __USE_GNU
# include <bits/wordsize.h>
# include <endian.h>
# include <fcntl.h>

# ifdef __NR_sync_file_range2
#  undef __NR_sync_file_range
#  define __NR_sync_file_range __NR_sync_file_range2
# endif

# ifdef __NR_sync_file_range
int sync_file_range(int fd, off64_t offset, off64_t nbytes, unsigned int flags)
{
#  if defined __powerpc__ && __WORDSIZE == 64
	return INLINE_SYSCALL(sync_file_range, 4, fd, flags, offset, nbytes);
#  elif (defined __mips__ && _MIPS_SIM == _ABIO32) || \
	(defined(__UCLIBC_SYSCALL_ALIGN_64BIT__) && !(defined(__powerpc__) || defined(__xtensa__)))
	/* arch with 64-bit data in even reg alignment #2: [arcv2/others-in-future]
	 * stock syscall handler in kernel (reg hole punched)
	 * see libc/sysdeps/linux/common/posix_fadvise.c for more details */
	return INLINE_SYSCALL(sync_file_range, 7, fd, 0,
			OFF64_HI_LO(offset), OFF64_HI_LO(nbytes), flags);
#  elif defined __NR_sync_file_range2
	return INLINE_SYSCALL(sync_file_range, 6, fd, flags,
			OFF64_HI_LO(offset), OFF64_HI_LO(nbytes));
#  else
	return INLINE_SYSCALL(sync_file_range, 6, fd,
			OFF64_HI_LO(offset), OFF64_HI_LO(nbytes), flags);
#  endif
}
# endif
#endif
