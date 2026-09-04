import { Star } from 'lucide-react';

import { cn } from '@/lib/utils';

export function StarRating({
    value,
    onChange,
    size = 16,
    className,
}: {
    value: number;
    onChange?: (value: number) => void;
    size?: number;
    className?: string;
}) {
    const interactive = typeof onChange === 'function';

    return (
        <span className={cn('inline-flex items-center gap-0.5', className)}>
            {[1, 2, 3, 4, 5].map((star) => {
                const filled = star <= Math.round(value);
                const Node = interactive ? 'button' : 'span';

                return (
                    <Node
                        key={star}
                        type={interactive ? 'button' : undefined}
                        onClick={
                            interactive ? () => onChange?.(star) : undefined
                        }
                        className={cn(
                            interactive && 'cursor-pointer',
                            'leading-none',
                        )}
                        aria-label={interactive ? `${star} stars` : undefined}
                    >
                        <Star
                            style={{ width: size, height: size }}
                            className={cn(
                                filled
                                    ? 'fill-warning text-warning'
                                    : 'text-muted-foreground/40',
                            )}
                        />
                    </Node>
                );
            })}
        </span>
    );
}
