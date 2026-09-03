import { Dumbbell } from 'lucide-react';

import { cn } from '@/lib/utils';

export function AppLogo({
    className,
    withText = true,
}: {
    className?: string;
    withText?: boolean;
}) {
    return (
        <span
            className={cn('flex items-center gap-2 font-semibold', className)}
        >
            <span className="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-lg">
                <Dumbbell className="size-5" />
            </span>
            {withText && (
                <span className="text-lg tracking-tight">CoachPay</span>
            )}
        </span>
    );
}
