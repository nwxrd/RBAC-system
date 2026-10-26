import { Form, Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/AuditController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
} from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type AuditRow = {
    id: number;
    actor_name: string | null;
    action_label: string;
    subject_label: string | null;
    changes: string[];
    created_at: string | null;
};

type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginator<T> = {
    data: T[];
    links: PageLink[];
    last_page: number;
};

function linkLabel(label: string): string {
    return label
        .replaceAll('&laquo;', '«')
        .replaceAll('&raquo;', '»')
        .replaceAll(/<[^>]*>/g, '');
}

export default function Index({
    logs,
    filters,
}: {
    logs: Paginator<AuditRow>;
    filters: { search?: string };
}) {
    return (
        <>
            <Head title="Auditoría" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Auditoría"
                    description="Accesos y cambios registrados en el portal"
                />

                <Form
                    {...index.form()}
                    options={{ preserveState: true }}
                    className="flex max-w-md gap-2"
                >
                    {() => (
                        <>
                            <Input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Buscar por persona, acción o asunto"
                            />
                            <Button type="submit" variant="secondary">
                                Buscar
                            </Button>
                        </>
                    )}
                </Form>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Persona</TableHead>
                                <TableHead>Acción</TableHead>
                                <TableHead>Asunto</TableHead>
                                <TableHead>Cambios</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5}>
                                        No hay registros.
                                    </TableCell>
                                </TableRow>
                            )}
                            {logs.data.map((log) => (
                                <TableRow key={log.id}>
                                    <TableCell>{log.created_at ?? '—'}</TableCell>
                                    <TableCell>{log.actor_name ?? '—'}</TableCell>
                                    <TableCell>{log.action_label}</TableCell>
                                    <TableCell>
                                        {log.subject_label ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {log.changes.length === 0
                                            ? '—'
                                            : log.changes.join(', ')}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {logs.last_page > 1 && (
                    <Pagination>
                        <PaginationContent>
                            {logs.links.map((link, linkIndex) => (
                                <PaginationItem
                                    key={`${link.label}-${linkIndex}`}
                                >
                                    <PaginationLink
                                        href={link.url ?? undefined}
                                        isActive={link.active}
                                        size="default"
                                        className={
                                            link.url
                                                ? undefined
                                                : 'pointer-events-none opacity-50'
                                        }
                                        onClick={(event) => {
                                            event.preventDefault();

                                            if (link.url) {
                                                router.visit(link.url, {
                                                    preserveScroll: true,
                                                });
                                            }
                                        }}
                                    >
                                        {linkLabel(link.label)}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                        </PaginationContent>
                    </Pagination>
                )}
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Auditoría',
            href: index.url(),
        },
    ],
};
