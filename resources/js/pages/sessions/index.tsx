import { Head, router, useForm } from '@inertiajs/react';
import {
    addMonths,
    eachDayOfInterval,
    endOfMonth,
    endOfWeek,
    format,
    isSameDay,
    isSameMonth,
    parseISO,
    startOfMonth,
    startOfWeek,
    subMonths,
} from 'date-fns';
import { ChevronLeft, ChevronRight, Pencil, Plus, Trash2 } from 'lucide-react';
import { type FormEvent, useEffect, useMemo, useState } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { InputError } from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatMoney } from '@/lib/format';
import * as sessions from '@/routes/sessions';

type SessionRow = {
    id: number;
    client_id: number;
    client_name: string;
    scheduled_at: string;
    duration_minutes: number;
    rate: string;
    status: string;
    status_label: string;
    notes: string | null;
    invoiced: boolean;
};

type ClientOption = { id: number; name: string; default_rate: string | null };

type Props = {
    sessions: SessionRow[];
    month: string;
    clients: ClientOption[];
    currency: string;
};

const statusVariant: Record<
    string,
    'secondary' | 'success' | 'warning' | 'outline'
> = {
    scheduled: 'secondary',
    completed: 'success',
    cancelled: 'outline',
    no_show: 'warning',
};

function SessionDialog({
    open,
    onOpenChange,
    session,
    clients,
    defaultDate,
}: {
    open: boolean;
    onOpenChange: (value: boolean) => void;
    session: SessionRow | null;
    clients: ClientOption[];
    defaultDate: string | null;
}) {
    const form = useForm({
        client_id: '',
        scheduled_at: '',
        duration_minutes: '60',
        rate: '',
        status: 'scheduled',
        notes: '',
    });

    useEffect(() => {
        if (open) {
            form.setDefaults({
                client_id: session ? String(session.client_id) : '',
                scheduled_at: session
                    ? session.scheduled_at.slice(0, 16)
                    : defaultDate
                      ? `${defaultDate}T09:00`
                      : '',
                duration_minutes: session
                    ? String(session.duration_minutes)
                    : '60',
                rate: session?.rate ?? '',
                status: session?.status ?? 'scheduled',
                notes: session?.notes ?? '',
            });
            form.reset();
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, session, defaultDate]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };
        if (session) {
            form.put(sessions.update(session.id).url, options);
        } else {
            form.post(sessions.store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {session ? 'Edit session' : 'Log session'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="client_id">Client</Label>
                        <Select
                            id="client_id"
                            value={form.data.client_id}
                            onChange={(e) => {
                                const client = clients.find(
                                    (c) => String(c.id) === e.target.value,
                                );
                                form.setData((data) => ({
                                    ...data,
                                    client_id: e.target.value,
                                    rate:
                                        data.rate ||
                                        (client?.default_rate ?? ''),
                                }));
                            }}
                        >
                            <option value="">Choose a client…</option>
                            {clients.map((client) => (
                                <option key={client.id} value={client.id}>
                                    {client.name}
                                </option>
                            ))}
                        </Select>
                        <InputError message={form.errors.client_id} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="scheduled_at">Date & time</Label>
                            <Input
                                id="scheduled_at"
                                type="datetime-local"
                                value={form.data.scheduled_at}
                                onChange={(e) =>
                                    form.setData('scheduled_at', e.target.value)
                                }
                            />
                            <InputError message={form.errors.scheduled_at} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="duration_minutes">
                                Duration (min)
                            </Label>
                            <Input
                                id="duration_minutes"
                                type="number"
                                min="5"
                                value={form.data.duration_minutes}
                                onChange={(e) =>
                                    form.setData(
                                        'duration_minutes',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={form.errors.duration_minutes}
                            />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="rate">Rate</Label>
                            <Input
                                id="rate"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.rate}
                                onChange={(e) =>
                                    form.setData('rate', e.target.value)
                                }
                            />
                            <InputError message={form.errors.rate} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="status">Status</Label>
                            <Select
                                id="status"
                                value={form.data.status}
                                onChange={(e) =>
                                    form.setData('status', e.target.value)
                                }
                            >
                                <option value="scheduled">Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="no_show">No-show</option>
                            </Select>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
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
                            {session ? 'Save' : 'Log session'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function SessionsIndex({
    sessions: rows,
    month,
    clients,
    currency,
}: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<SessionRow | null>(null);
    const [defaultDate, setDefaultDate] = useState<string | null>(null);

    const monthDate = useMemo(() => parseISO(month), [month]);

    const days = useMemo(() => {
        return eachDayOfInterval({
            start: startOfWeek(startOfMonth(monthDate)),
            end: endOfWeek(endOfMonth(monthDate)),
        });
    }, [monthDate]);

    const byDay = useMemo(() => {
        const map = new Map<string, SessionRow[]>();
        for (const row of rows) {
            const key = format(parseISO(row.scheduled_at), 'yyyy-MM-dd');
            map.set(key, [...(map.get(key) ?? []), row]);
        }
        return map;
    }, [rows]);

    const goMonth = (next: boolean) => {
        const target = next ? addMonths(monthDate, 1) : subMonths(monthDate, 1);
        router.get(
            sessions.index().url,
            { month: format(target, 'yyyy-MM-dd') },
            { preserveState: true, preserveScroll: true },
        );
    };

    const openNew = (date: string | null) => {
        setEditing(null);
        setDefaultDate(date);
        setOpen(true);
    };

    return (
        <AppLayout
            title="Sessions"
            actions={
                <Button size="sm" onClick={() => openNew(null)}>
                    <Plus className="size-4" /> Log session
                </Button>
            }
        >
            <Head title="Sessions" />

            {clients.length === 0 && (
                <Card className="mb-4">
                    <CardContent className="text-muted-foreground py-6 text-center text-sm">
                        Add a client first, then you can log sessions for them.
                    </CardContent>
                </Card>
            )}

            <Tabs defaultValue="calendar">
                <TabsList>
                    <TabsTrigger value="calendar">Calendar</TabsTrigger>
                    <TabsTrigger value="list">List</TabsTrigger>
                </TabsList>

                <TabsContent value="calendar">
                    <Card>
                        <CardContent className="p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="font-semibold">
                                    {format(monthDate, 'MMMM yyyy')}
                                </h2>
                                <div className="flex gap-1">
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        onClick={() => goMonth(false)}
                                    >
                                        <ChevronLeft className="size-4" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        onClick={() => goMonth(true)}
                                    >
                                        <ChevronRight className="size-4" />
                                    </Button>
                                </div>
                            </div>
                            <div className="text-muted-foreground grid grid-cols-7 gap-1 text-center text-xs font-medium">
                                {[
                                    'Mon',
                                    'Tue',
                                    'Wed',
                                    'Thu',
                                    'Fri',
                                    'Sat',
                                    'Sun',
                                ].map((label) => (
                                    <div key={label} className="py-1">
                                        {label}
                                    </div>
                                ))}
                            </div>
                            <div className="grid grid-cols-7 gap-1">
                                {days.map((day) => {
                                    const key = format(day, 'yyyy-MM-dd');
                                    const dayRows = byDay.get(key) ?? [];
                                    return (
                                        <button
                                            key={key}
                                            onClick={() => openNew(key)}
                                            className={[
                                                'hover:border-primary min-h-24 rounded-md border p-1 text-left align-top transition-colors',
                                                isSameMonth(day, monthDate)
                                                    ? ''
                                                    : 'bg-muted/40 text-muted-foreground',
                                                isSameDay(day, new Date())
                                                    ? 'border-primary'
                                                    : '',
                                            ].join(' ')}
                                        >
                                            <span className="text-xs font-medium">
                                                {format(day, 'd')}
                                            </span>
                                            <div className="mt-1 space-y-1">
                                                {dayRows
                                                    .slice(0, 3)
                                                    .map((row) => (
                                                        <div
                                                            key={row.id}
                                                            className="bg-accent text-accent-foreground truncate rounded px-1 py-0.5 text-[11px]"
                                                        >
                                                            {format(
                                                                parseISO(
                                                                    row.scheduled_at,
                                                                ),
                                                                'HH:mm',
                                                            )}{' '}
                                                            {row.client_name}
                                                        </div>
                                                    ))}
                                                {dayRows.length > 3 && (
                                                    <div className="text-muted-foreground text-[11px]">
                                                        +{dayRows.length - 3}{' '}
                                                        more
                                                    </div>
                                                )}
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="list">
                    <Card>
                        <CardContent className="p-0">
                            {rows.length === 0 ? (
                                <p className="text-muted-foreground p-10 text-center text-sm">
                                    No sessions in this window.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>When</TableHead>
                                            <TableHead>Client</TableHead>
                                            <TableHead>Duration</TableHead>
                                            <TableHead>Rate</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {[...rows]
                                            .sort((a, b) =>
                                                a.scheduled_at.localeCompare(
                                                    b.scheduled_at,
                                                ),
                                            )
                                            .map((row) => (
                                                <TableRow key={row.id}>
                                                    <TableCell>
                                                        {formatDateTime(
                                                            row.scheduled_at,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {row.client_name}
                                                    </TableCell>
                                                    <TableCell>
                                                        {row.duration_minutes}{' '}
                                                        min
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatMoney(
                                                            row.rate,
                                                            currency,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                statusVariant[
                                                                    row.status
                                                                ] ?? 'secondary'
                                                            }
                                                        >
                                                            {row.status_label}
                                                        </Badge>
                                                        {row.invoiced && (
                                                            <Badge
                                                                variant="outline"
                                                                className="ml-1"
                                                            >
                                                                Invoiced
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex justify-end gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => {
                                                                    setEditing(
                                                                        row,
                                                                    );
                                                                    setDefaultDate(
                                                                        null,
                                                                    );
                                                                    setOpen(
                                                                        true,
                                                                    );
                                                                }}
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                            <ConfirmDialog
                                                                trigger={
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                    >
                                                                        <Trash2 className="size-4" />
                                                                    </Button>
                                                                }
                                                                title="Delete session?"
                                                                description="This session will be removed."
                                                                confirmLabel="Delete"
                                                                onConfirm={() =>
                                                                    router.delete(
                                                                        sessions.destroy(
                                                                            row.id,
                                                                        ).url,
                                                                        {
                                                                            preserveScroll: true,
                                                                        },
                                                                    )
                                                                }
                                                            />
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            <SessionDialog
                open={open}
                onOpenChange={setOpen}
                session={editing}
                clients={clients}
                defaultDate={defaultDate}
            />
        </AppLayout>
    );
}
