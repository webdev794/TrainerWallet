import { Head, Link, router } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useEffect, useState } from 'react';

import { StarRating } from '@/components/star-rating';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import MarketingLayout from '@/layouts/marketing-layout';
import * as trainers from '@/routes/trainers';

type TrainerRow = {
    slug: string;
    business_name: string;
    headline: string | null;
    city: string | null;
    rating_avg: string;
    rating_count: number;
    services_count: number;
    logo_url: string | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function TrainersIndex({
    trainers: page,
    filters,
}: {
    trainers: Paginated<TrainerRow>;
    filters: { search: string };
}) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        const t = setTimeout(() => {
            router.get(
                trainers.index().url,
                { search },
                { preserveState: true, replace: true, preserveScroll: true },
            );
        }, 250);
        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <MarketingLayout>
            <Head title="Find a trainer" />

            <section className="mx-auto w-full max-w-6xl px-4 py-14">
                <div className="mx-auto max-w-2xl text-center">
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Find a trainer
                    </h1>
                    <p className="text-muted-foreground mt-3">
                        Browse coaches, see what people say, and book a session
                        in a couple of taps.
                    </p>
                    <Input
                        className="mx-auto mt-6 max-w-md"
                        placeholder="Search by name, focus or city"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                {page.data.length === 0 ? (
                    <p className="text-muted-foreground mt-16 text-center text-sm">
                        No trainers match that search yet.
                    </p>
                ) : (
                    <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {page.data.map((trainer) => (
                            <Card key={trainer.slug} className="flex flex-col">
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        {trainer.logo_url ? (
                                            <img
                                                src={trainer.logo_url}
                                                alt=""
                                                className="size-12 rounded-lg object-cover"
                                            />
                                        ) : (
                                            <div className="bg-primary text-primary-foreground flex size-12 items-center justify-center rounded-lg text-lg font-semibold">
                                                {trainer.business_name.charAt(
                                                    0,
                                                )}
                                            </div>
                                        )}
                                        <div>
                                            <CardTitle className="text-base">
                                                {trainer.business_name}
                                            </CardTitle>
                                            {trainer.city && (
                                                <p className="text-muted-foreground mt-0.5 flex items-center gap-1 text-xs">
                                                    <MapPin className="size-3" />
                                                    {trainer.city}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="flex flex-1 flex-col gap-3">
                                    {trainer.headline && (
                                        <p className="text-muted-foreground text-sm">
                                            {trainer.headline}
                                        </p>
                                    )}
                                    <div className="flex items-center gap-2 text-sm">
                                        <StarRating
                                            value={Number(trainer.rating_avg)}
                                        />
                                        <span className="text-muted-foreground">
                                            {trainer.rating_count > 0
                                                ? `${Number(trainer.rating_avg).toFixed(1)} (${trainer.rating_count})`
                                                : 'No reviews yet'}
                                        </span>
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        {trainer.services_count} bookable
                                        service
                                        {trainer.services_count === 1
                                            ? ''
                                            : 's'}
                                    </p>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="mt-auto"
                                    >
                                        <Link
                                            href={
                                                trainers.show(trainer.slug).url
                                            }
                                        >
                                            View profile
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {page.links.length > 3 && (
                    <div className="mt-8 flex flex-wrap justify-center gap-1">
                        {page.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() =>
                                    link.url &&
                                    router.get(
                                        link.url,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </section>
        </MarketingLayout>
    );
}
