import { Head, useForm } from '@inertiajs/react';
import { type FormEvent, useMemo, useState } from 'react';

import { InputError } from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import ClientPortalLayout from '@/layouts/client-portal-layout';
import { formatMoney } from '@/lib/format';
import * as portalBookings from '@/routes/portal/bookings';

type Service = {
    id: number;
    name: string;
    type: string;
    amount: string;
    duration_minutes: number | null;
    needs_slot: boolean;
};

type TrainerOption = {
    slug: string;
    business_name: string;
    currency: string;
    services: Service[];
};

export default function PortalBook({
    trainers,
    prefill,
}: {
    trainers: TrainerOption[];
    prefill: { trainer: string | null; package: number | null };
}) {
    const [trainerSlug, setTrainerSlug] = useState(
        prefill.trainer && trainers.some((t) => t.slug === prefill.trainer)
            ? prefill.trainer
            : (trainers[0]?.slug ?? ''),
    );

    const trainer = useMemo(
        () => trainers.find((t) => t.slug === trainerSlug),
        [trainers, trainerSlug],
    );

    const form = useForm({
        package_id: prefill.package ? String(prefill.package) : '',
        scheduled_at: '',
        notes: '',
    });

    const selected = trainer?.services.find(
        (s) => String(s.id) === form.data.package_id,
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(portalBookings.store().url);
    };

    if (trainers.length === 0) {
        return (
            <ClientPortalLayout title="Book a session">
                <Head title="Book" />
                <Card>
                    <CardContent className="text-muted-foreground py-10 text-center text-sm">
                        No trainers are offering bookable services yet.
                    </CardContent>
                </Card>
            </ClientPortalLayout>
        );
    }

    return (
        <ClientPortalLayout title="Book a session">
            <Head title="Book" />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle>Instant booking</CardTitle>
                    <p className="text-muted-foreground text-sm">
                        Pick a service — an invoice is generated straight away
                        and you pay to confirm.
                    </p>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="trainer">Trainer</Label>
                            <Select
                                id="trainer"
                                value={trainerSlug}
                                onChange={(e) => {
                                    setTrainerSlug(e.target.value);
                                    form.setData('package_id', '');
                                }}
                            >
                                {trainers.map((t) => (
                                    <option key={t.slug} value={t.slug}>
                                        {t.business_name}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="package_id">Service</Label>
                            <Select
                                id="package_id"
                                value={form.data.package_id}
                                onChange={(e) =>
                                    form.setData('package_id', e.target.value)
                                }
                            >
                                <option value="">Choose a service…</option>
                                {trainer?.services.map((service) => (
                                    <option key={service.id} value={service.id}>
                                        {service.name} —{' '}
                                        {formatMoney(
                                            service.amount,
                                            trainer.currency,
                                        )}
                                    </option>
                                ))}
                            </Select>
                            <InputError message={form.errors.package_id} />
                        </div>

                        {selected?.needs_slot && (
                            <div className="space-y-2">
                                <Label htmlFor="scheduled_at">
                                    Date & time
                                </Label>
                                <Input
                                    id="scheduled_at"
                                    type="datetime-local"
                                    value={form.data.scheduled_at}
                                    onChange={(e) =>
                                        form.setData(
                                            'scheduled_at',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.scheduled_at}
                                />
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="notes">
                                Notes for your trainer (optional)
                            </Label>
                            <Textarea
                                id="notes"
                                value={form.data.notes}
                                onChange={(e) =>
                                    form.setData('notes', e.target.value)
                                }
                            />
                        </div>

                        {selected && (
                            <div className="bg-muted flex items-center justify-between rounded-md p-3 text-sm">
                                <span>Total</span>
                                <span className="text-lg font-semibold">
                                    {formatMoney(
                                        selected.amount,
                                        trainer?.currency ?? 'INR',
                                    )}
                                </span>
                            </div>
                        )}

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing || !form.data.package_id}
                        >
                            Book & go to payment
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </ClientPortalLayout>
    );
}
