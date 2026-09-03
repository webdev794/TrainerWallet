import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { AppLogo } from '@/components/app-logo';
import { InputError } from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Toaster } from '@/components/ui/sonner';
import * as onboarding from '@/routes/onboarding';

type Props = {
    profile: {
        business_name: string;
        currency: string;
        upi_vpa: string | null;
        invoice_prefix: string;
        logo_url: string | null;
    };
    currencies: string[];
};

export default function Onboarding({ profile, currencies }: Props) {
    const form = useForm<{
        business_name: string;
        currency: string;
        upi_vpa: string;
        invoice_prefix: string;
        logo: File | null;
    }>({
        business_name: profile.business_name,
        currency: profile.currency,
        upi_vpa: profile.upi_vpa ?? '',
        invoice_prefix: profile.invoice_prefix,
        logo: null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(onboarding.store.url(), { forceFormData: true });
    };

    return (
        <div className="bg-muted/30 flex min-h-screen items-center justify-center p-6">
            <Head title="Set up your workspace" />

            <Card className="w-full max-w-lg">
                <CardHeader className="items-center text-center">
                    <AppLogo />
                    <CardTitle className="mt-2">
                        Set up your workspace
                    </CardTitle>
                    <CardDescription>
                        A few details so your invoices look right. You can
                        change these later in Settings.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="business_name">Business name</Label>
                            <Input
                                id="business_name"
                                value={form.data.business_name}
                                onChange={(e) =>
                                    form.setData(
                                        'business_name',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.business_name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="currency">Currency</Label>
                                <Select
                                    id="currency"
                                    value={form.data.currency}
                                    onChange={(e) =>
                                        form.setData('currency', e.target.value)
                                    }
                                >
                                    {currencies.map((code) => (
                                        <option key={code} value={code}>
                                            {code}
                                        </option>
                                    ))}
                                </Select>
                                <InputError message={form.errors.currency} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="invoice_prefix">
                                    Invoice prefix
                                </Label>
                                <Input
                                    id="invoice_prefix"
                                    value={form.data.invoice_prefix}
                                    onChange={(e) =>
                                        form.setData(
                                            'invoice_prefix',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.invoice_prefix}
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="upi_vpa">
                                UPI ID{' '}
                                <span className="text-muted-foreground">
                                    (optional)
                                </span>
                            </Label>
                            <Input
                                id="upi_vpa"
                                placeholder="yourname@okhdfcbank"
                                value={form.data.upi_vpa}
                                onChange={(e) =>
                                    form.setData('upi_vpa', e.target.value)
                                }
                            />
                            <InputError message={form.errors.upi_vpa} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="logo">
                                Logo{' '}
                                <span className="text-muted-foreground">
                                    (optional, PNG/JPG)
                                </span>
                            </Label>
                            <Input
                                id="logo"
                                type="file"
                                accept="image/*"
                                onChange={(e) =>
                                    form.setData(
                                        'logo',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <InputError message={form.errors.logo} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                        >
                            Go to dashboard
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Toaster />
        </div>
    );
}
