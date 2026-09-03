import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import * as React from 'react';

import { cn } from '@/lib/utils';

const Sheet = DialogPrimitive.Root;
const SheetTrigger = DialogPrimitive.Trigger;
const SheetClose = DialogPrimitive.Close;

function SheetContent({
    className,
    children,
    side = 'left',
    ...props
}: React.ComponentProps<typeof DialogPrimitive.Content> & {
    side?: 'left' | 'right';
}) {
    return (
        <DialogPrimitive.Portal>
            <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
            <DialogPrimitive.Content
                className={cn(
                    'fixed inset-y-0 z-50 h-full w-3/4 max-w-sm bg-sidebar text-sidebar-foreground shadow-lg transition ease-in-out data-[state=open]:animate-in data-[state=closed]:animate-out',
                    side === 'left' ? 'left-0' : 'right-0',
                    className,
                )}
                {...props}
            >
                {children}
                <DialogPrimitive.Close className="absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100">
                    <X className="size-5" />
                    <span className="sr-only">Close</span>
                </DialogPrimitive.Close>
            </DialogPrimitive.Content>
        </DialogPrimitive.Portal>
    );
}

export { Sheet, SheetTrigger, SheetClose, SheetContent };
