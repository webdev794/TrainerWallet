import { Head } from '@inertiajs/react';

import { StarRating } from '@/components/star-rating';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';

type ReviewRow = {
    id: number;
    service: string;
    author: string;
    rating: number;
    body: string | null;
    improvement: string | null;
    created_at: string | null;
};

type ServiceRow = {
    service: string;
    avg_rating: number;
    review_count: number;
};

export default function ReviewsIndex({
    reviews,
    byService,
    summary,
}: {
    reviews: ReviewRow[];
    byService: ServiceRow[];
    summary: { rating_avg: string; rating_count: number };
}) {
    return (
        <AppLayout title="Reviews">
            <Head title="Reviews" />

            <div className="grid gap-4 sm:grid-cols-[240px_1fr]">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-muted-foreground text-sm font-medium">
                            Overall rating
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">
                            {summary.rating_count > 0
                                ? Number(summary.rating_avg).toFixed(1)
                                : '—'}
                        </p>
                        <StarRating value={Number(summary.rating_avg)} />
                        <p className="text-muted-foreground mt-1 text-xs">
                            {summary.rating_count} review
                            {summary.rating_count === 1 ? '' : 's'}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>By service</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {byService.length === 0 ? (
                            <p className="text-muted-foreground p-6 text-center text-sm">
                                No reviews yet.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Service</TableHead>
                                        <TableHead>Rating</TableHead>
                                        <TableHead>Reviews</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {byService.map((row) => (
                                        <TableRow key={row.service}>
                                            <TableCell className="font-medium">
                                                {row.service}
                                            </TableCell>
                                            <TableCell>
                                                <span className="flex items-center gap-1">
                                                    <StarRating
                                                        value={row.avg_rating}
                                                        size={13}
                                                    />
                                                    {row.avg_rating}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                {row.review_count}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>All reviews</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {reviews.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No reviews yet — publish some bookable services and
                            ask happy clients to leave one.
                        </p>
                    ) : (
                        reviews.map((review) => (
                            <div
                                key={review.id}
                                className="space-y-1 rounded-md border p-3"
                            >
                                <div className="flex items-center justify-between">
                                    <div>
                                        <span className="text-sm font-medium">
                                            {review.author}
                                        </span>
                                        <span className="text-muted-foreground text-xs">
                                            {' '}
                                            · {review.service}
                                            {review.created_at
                                                ? ` · ${review.created_at}`
                                                : ''}
                                        </span>
                                    </div>
                                    <StarRating
                                        value={review.rating}
                                        size={14}
                                    />
                                </div>
                                {review.body && (
                                    <p className="text-sm">{review.body}</p>
                                )}
                                {review.improvement && (
                                    <p className="text-muted-foreground text-sm">
                                        <span className="font-medium">
                                            Improvement:
                                        </span>{' '}
                                        {review.improvement}
                                    </p>
                                )}
                            </div>
                        ))
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
