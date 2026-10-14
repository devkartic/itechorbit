import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';
const buttonVariants = cva('inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full text-sm font-semibold transition-all duration-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600 disabled:pointer-events-none disabled:opacity-60', {
  variants: { variant: { default: 'bg-blue-600 text-white shadow-[0_5px_15px_-6px_#7040cf80] hover:bg-blue-700', outline: 'border border-slate-200 bg-white text-slate-900 hover:bg-slate-50', ghost: 'text-slate-600 hover:bg-slate-100', destructive: 'bg-red-700 text-white hover:bg-red-800' }, size: { default: 'h-11 px-5', sm: 'h-9 px-3', lg: 'h-13 px-7', icon: 'size-11' } },
  defaultVariants: { variant: 'default', size: 'default' },
});
export function Button({ className, variant, size, asChild = false, ...props }: ComponentProps<'button'> & VariantProps<typeof buttonVariants> & { asChild?: boolean }) {
  const Component = asChild ? Slot : 'button';
  return <Component className={cn(buttonVariants({ variant, size }), className)} {...props} />;
}
