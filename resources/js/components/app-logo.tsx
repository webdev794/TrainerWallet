import { CreditCard, Zap } from 'lucide-react';
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
            {/* Logo Mark */}
            <span className="bg-primary text-primary-foreground relative flex size-9 items-center justify-center overflow-hidden rounded-xl">
                {/* TP Monogram */}
                <span className="relative z-10 text-[15px] font-black tracking-[-0.08em]">
                    TP
                </span>

                {/* Motion / Running Accent */}
                <span className="absolute bottom-[7px] left-[4px] h-[2px] w-3 rounded-full bg-yellow-400" />
                <span className="absolute bottom-[4px] left-[6px] h-[2px] w-2 rounded-full bg-yellow-400" />

                {/* Payment Accent */}
                <CreditCard
                    className="absolute -right-1 -bottom-1 size-5 rotate-[-18deg] text-yellow-400"
                    strokeWidth={2.5}
                />

                {/* Small energy accent */}
                <Zap
                    className="absolute top-[2px] right-[2px] size-2.5 text-yellow-400"
                    strokeWidth={3}
                />
            </span>

            {/* Brand Name */}
            {withText && (
                <span className="text-lg tracking-tight">
                    Trainer<span className="text-yellow-500">Pay</span>
                </span>
            )}
        </span>
    );
}
