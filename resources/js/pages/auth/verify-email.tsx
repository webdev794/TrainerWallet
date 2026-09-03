import { Head, router, useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import * as verification from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const form = useForm({});

    const resend = () => {
        form.post(verification.send.url());
    };

    return (
        <AuthLayout
            title="Verify your email"
            description="We sent a verification link to your inbox"
        >
            <Head title="Verify email" />

            {status === 'verification-link-sent' && (
                <div className="bg-success/10 text-success rounded-md p-3 text-sm">
                    A fresh verification link has been sent to your email
                    address.
                </div>
            )}

            <p className="text-muted-foreground text-sm">
                Click the link in the email to activate your account.
                Didn&rsquo;t get it? Request another below.
            </p>

            <div className="space-y-3">
                <Button
                    className="w-full"
                    onClick={resend}
                    disabled={form.processing}
                >
                    Resend verification email
                </Button>
                <Button
                    variant="ghost"
                    className="w-full"
                    onClick={() => router.post(logout.url())}
                >
                    Log out
                </Button>
            </div>
        </AuthLayout>
    );
}
