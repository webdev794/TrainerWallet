import { Head, Link } from '@inertiajs/react';
import { Clock, MapPin } from 'lucide-react';

import { StarRating } from '@/components/star-rating';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import MarketingLayout from '@/layouts/marketing-layout';
import { formatMoney } from '@/lib/format';
import { login } from '@/routes';
import * as portal from '@/routes/portal';

type Service = {
    id: number;
    name: string;
    description: string | null;
    type: string;
    type_label: string;
    amount: string;
    duration_minutes: number | null;
    sessions_count: number | null;
    rating: number | null;
    reviews_count: number;
};

type ReviewRow = {
    id: number;
    rating: number;
    body: string | null;
    service: string;
    author: string;
    created_at: string | null;
};

type Trainer = {
    slug: string;
    business_name: string;
    headline: string | null;
    bio: string | null;
    city: string | null;
    currency: string;
    rating_avg: string;
    rating_count: number;
    logo_url: string | null;
};

export default function TrainerShow({
    trainer,
    packages,
    reviews,
    canBook,
}: {
    trainer: Trainer;
    packages: Service[];
    reviews: ReviewRow[];
    canBook: boolean;
}) {
    return (
        <MarketingLayout>
            <Head title={trainer.business_name} />

            <div className="mx-auto w-full max-w-4xl px-4 py-12">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                    {trainer.logo_url ? (
                        <img
                            src={trainer.logo_url}
                            alt=""
                            className="size-20 rounded-xl object-cover"
                        />
                    ) : (
                        <div className="bg-primary text-primary-foreground flex size-20 items-center justify-center rounded-xl text-2xl font-bold">
                            {trainer.business_name.charAt(0)}
                        </div>
                    )}
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            {trainer.business_name}
                        </h1>
                        {trainer.headline && (
                            <p className="text-muted-foreground">
                                {trainer.headline}
                            </p>
                        )}
                        <div className="mt-2 flex flex-wrap items-center gap-3 text-sm">
                            <span className="flex items-center gap-1.5">
                                <StarRating
                                    value={Number(trainer.rating_avg)}
                                />
                                <span className="text-muted-foreground">
                                    {trainer.rating_count > 0
                                        ? `${Number(trainer.rating_avg).toFixed(1)} · ${trainer.rating_count} review${trainer.rating_count === 1 ? '' : 's'}`
                                        : 'No reviews yet'}
                                </span>
                            </span>
                            {trainer.city && (
                                <span className="text-muted-foreground flex items-center gap-1">
                                    <MapPin className="size-4" /> {trainer.city}
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {trainer.bio && (
                    <p className="text-muted-foreground mt-6 text-sm leading-relaxed">
                        {trainer.bio}
                    </p>
                )}

                <h2 className="mt-10 text-lg font-semibold">Services</h2>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    {packages.map((service) => (
                        <Card key={service.id}>
                            <CardHeader>
                                <div className="flex items-start justify-between gap-2">
                                    <CardTitle className="text-base">
                                        {service.name}
                                    </CardTitle>
                                    <Badge variant="secondary">
                                        {service.type_label}
                                    </Badge>
                                </div>
                                <p className="text-xl font-bold">
                                    {formatMoney(
                                        service.amount,
                                        trainer.currency,
                                    )}
                                </p>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {service.description && (
                                    <p className="text-muted-foreground">
                                        {service.description}
                                    </p>
                                )}
                                <div className="text-muted-foreground flex flex-wrap gap-3 text-xs">
                                    {service.duration_minutes && (
                                        <span className="flex items-center gap-1">
                                            <Clock className="size-3" />
                                            {service.duration_minutes} min
                                        </span>
                                    )}
                                    {service.sessions_count && (
                                        <span>
                                            {service.sessions_count} sessions
                                        </span>
                                    )}
                                    {service.reviews_count > 0 &&
                                        service.rating !== null && (
                                            <span className="flex items-center gap-1">
                                                <StarRating
                                                    value={service.rating}
                                                    size={12}
                                                />
                                                {service.rating} (
                                                {service.reviews_count})
                                            </span>
                                        )}
                                </div>
                                {canBook ? (
                                    <Button asChild className="w-full">
                                        <Link
                                            href={`${portal.book().url}?trainer=${trainer.slug}&package=${service.id}`}
                                        >
                                            Book now
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="w-full"
                                    >
                                        <Link href={login.url()}>
                                            Log in as a client to book
                                        </Link>
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                    {packages.length === 0 && (
                        <p className="text-muted-foreground text-sm">
                            This trainer has no bookable services listed right
                            now.
                        </p>
                    )}
                </div>

                <h2 className="mt-10 text-lg font-semibold">Reviews</h2>
                <div className="mt-4 space-y-4">
                    {reviews.length === 0 && (
                        <p className="text-muted-foreground text-sm">
                            No reviews yet.
                        </p>
                    )}
                    {reviews.map((review) => (
                        <Card key={review.id}>
                            <CardContent className="space-y-1 py-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">
                                        {review.author}
                                    </span>
                                    <StarRating
                                        value={review.rating}
                                        size={14}
                                    />
                                </div>
                                <p className="text-muted-foreground text-xs">
                                    {review.service}
                                    {review.created_at
                                        ? ` · ${review.created_at}`
                                        : ''}
                                </p>
                                {review.body && (
                                    <p className="text-sm">{review.body}</p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </MarketingLayout>
    );
}
