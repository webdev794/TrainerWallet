import { Head, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { StarRating } from '@/components/star-rating';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import ClientPortalLayout from '@/layouts/client-portal-layout';
import * as portalReviews from '@/routes/portal/reviews';

type Reviewable = {
    package_id: number;
    service_name: string;
    trainer_name: string;
};

type MyReview = {
    id: number;
    package_id: number;
    service_name: string;
    trainer_name: string;
    rating: number;
    body: string | null;
    improvement: string | null;
    created_at: string | null;
};

function ReviewDialog({
    open,
    onOpenChange,
    packageId,
    heading,
    existing,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    packageId: number | null;
    heading: string;
    existing: MyReview | null;
}) {
    const form = useForm({
        package_id: packageId ? String(packageId) : '',
        rating: existing?.rating ?? 5,
        body: existing?.body ?? '',
        improvement: existing?.improvement ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };
        if (existing) {
            form.put(portalReviews.update(existing.id).url, options);
        } else {
            form.post(portalReviews.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{heading}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label>Rating</Label>
                        <StarRating
                            value={form.data.rating}
                            onChange={(r) => form.setData('rating', r)}
                            size={28}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="body">What went well?</Label>
                        <Textarea
                            id="body"
                            value={form.data.body}
                            onChange={(e) =>
                                form.setData('body', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="improvement">
                            How could it be improved?
                        </Label>
                        <Textarea
                            id="improvement"
                            value={form.data.improvement}
                            onChange={(e) =>
                                form.setData('improvement', e.target.value)
                            }
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {existing ? 'Update review' : 'Submit review'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function PortalReviews({
    reviewable,
    reviews,
}: {
    reviewable: Reviewable[];
    reviews: MyReview[];
}) {
    const [dialog, setDialog] = useState<{
        packageId: number | null;
        heading: string;
        existing: MyReview | null;
    } | null>(null);

    return (
        <ClientPortalLayout title="Reviews">
            <Head title="Reviews" />

            {reviewable.length > 0 && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>Services you can review</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {reviewable.map((item) => (
                            <div
                                key={item.package_id}
                                className="flex items-center justify-between rounded-md border p-3 text-sm"
                            >
                                <div>
                                    <p className="font-medium">
                                        {item.service_name}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {item.trainer_name}
                                    </p>
                                </div>
                                <Button
                                    size="sm"
                                    onClick={() =>
                                        setDialog({
                                            packageId: item.package_id,
                                            heading: `Review ${item.service_name}`,
                                            existing: null,
                                        })
                                    }
                                >
                                    Write a review
                                </Button>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>Your reviews</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {reviews.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            You haven&rsquo;t reviewed anything yet.
                        </p>
                    ) : (
                        reviews.map((review) => (
                            <div
                                key={review.id}
                                className="space-y-1 rounded-md border p-3"
                            >
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {review.service_name}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {review.trainer_name}
                                            {review.created_at
                                                ? ` · ${review.created_at}`
                                                : ''}
                                        </p>
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
                                            Suggested:
                                        </span>{' '}
                                        {review.improvement}
                                    </p>
                                )}
                                <div className="flex gap-2 pt-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            setDialog({
                                                packageId: review.package_id,
                                                heading: `Edit review — ${review.service_name}`,
                                                existing: review,
                                            })
                                        }
                                    >
                                        Edit
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="ghost" size="sm">
                                                Delete
                                            </Button>
                                        }
                                        title="Delete review?"
                                        description="This removes your review and updates the trainer's rating."
                                        confirmLabel="Delete"
                                        onConfirm={() =>
                                            router.delete(
                                                portalReviews.destroy(review.id)
                                                    .url,
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        ))
                    )}
                </CardContent>
            </Card>

            {dialog && (
                <ReviewDialog
                    open={true}
                    onOpenChange={(v) => !v && setDialog(null)}
                    packageId={dialog.packageId}
                    heading={dialog.heading}
                    existing={dialog.existing}
                />
            )}
        </ClientPortalLayout>
    );
}
