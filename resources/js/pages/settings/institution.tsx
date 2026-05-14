import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import {
    Building2,
    Check,
    Fingerprint,
    Mail,
    MapPin,
    Phone,
    Save,
    Upload,
} from 'lucide-react';
import InstitutionController from '@/actions/App/Http/Controllers/Settings/InstitutionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { InstitutionLogoUpload } from '@/components/institution-logo-upload';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/institution';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Datos institucionales',
        href: edit().url,
    },
];

interface InstitutionProps {
    institution: {
        name: string;
        address: string | null;
        email: string | null;
        phone: string | null;
        code: string | null;
        logo_url: string | null;
        favicon_url: string | null;
    } | null;
}

export default function InstitutionSettings({ institution }: InstitutionProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Datos institucionales" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Datos Institucionales
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Gestiona los datos de contacto y parámetros
                                generales de la institución.
                            </p>
                        </div>
                        <div className="flex items-center gap-4">
                            <Button
                                type="submit"
                                form="institution-form"
                            >
                                <Save className="mr-2 h-4 w-4" />
                                Guardar cambios
                            </Button>
                        </div>
                    </div>

                    {/* Form Card */}
                    <Card className="overflow-hidden p-0">
                        {/* Header estilo tabla */}
                        <div className="flex items-center gap-2 rounded-t-xl border-b bg-neutral-50/50 px-6 py-3 dark:bg-neutral-800/30">
                            <Building2 className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                            <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                Información de la Institución
                            </span>
                        </div>

                        <CardContent className="p-6">
                            <Form
                                id="institution-form"
                                {...InstitutionController.update.form()}
                                options={{
                                    preserveScroll: true,
                                }}
                            >
                                {({ processing, recentlySuccessful, errors }) => (
                                    <>
                                        <Transition
                                            show={recentlySuccessful}
                                            enter="transition ease-in-out"
                                            enterFrom="opacity-0"
                                            leave="transition ease-in-out"
                                            leaveTo="opacity-0"
                                        >
                                            <p className="mb-4 flex items-center gap-1.5 text-sm text-green-600 dark:text-green-400">
                                                <Check className="h-4 w-4" />
                                                Guardado correctamente
                                            </p>
                                        </Transition>

                                        <div className="grid gap-6 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor="name"
                                                    className="flex items-center gap-2"
                                                >
                                                    <Building2 className="h-4 w-4 text-neutral-400 dark:text-neutral-500" />
                                                    Nombre de la Institución
                                                </Label>
                                                <Input
                                                    id="name"
                                                    defaultValue={
                                                        institution?.name ?? ''
                                                    }
                                                    name="name"
                                                    required
                                                    placeholder="Ej: Amantina de Sucre"
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor="code"
                                                    className="flex items-center gap-2"
                                                >
                                                    <Fingerprint className="h-4 w-4 text-neutral-400 dark:text-neutral-500" />
                                                    Código Institucional
                                                </Label>
                                                <Input
                                                    id="code"
                                                    defaultValue={
                                                        institution?.code ?? ''
                                                    }
                                                    name="code"
                                                    placeholder="Ej: AM-001"
                                                />
                                                <InputError
                                                    message={errors.code}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor="email"
                                                    className="flex items-center gap-2"
                                                >
                                                    <Mail className="h-4 w-4 text-neutral-400 dark:text-neutral-500" />
                                                    Correo Electrónico
                                                </Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    defaultValue={
                                                        institution?.email ?? ''
                                                    }
                                                    name="email"
                                                    placeholder="contacto@institucion.com"
                                                />
                                                <InputError
                                                    message={errors.email}
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor="phone"
                                                    className="flex items-center gap-2"
                                                >
                                                    <Phone className="h-4 w-4 text-neutral-400 dark:text-neutral-500" />
                                                    Teléfono de Contacto
                                                </Label>
                                                <Input
                                                    id="phone"
                                                    defaultValue={
                                                        institution?.phone ?? ''
                                                    }
                                                    name="phone"
                                                    placeholder="0412-0000000"
                                                />
                                                <InputError
                                                    message={errors.phone}
                                                />
                                            </div>

                                            <div className="space-y-2 md:col-span-2">
                                                <Label
                                                    htmlFor="address"
                                                    className="flex items-center gap-2"
                                                >
                                                    <MapPin className="h-4 w-4 text-neutral-400 dark:text-neutral-500" />
                                                    Dirección
                                                </Label>
                                                <Input
                                                    id="address"
                                                    defaultValue={
                                                        institution?.address ??
                                                        ''
                                                    }
                                                    name="address"
                                                    placeholder="Dirección física completa"
                                                />
                                                <InputError
                                                    message={errors.address}
                                                />
                                            </div>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>

                    {/* Logo Upload Section */}
                    <Card className="overflow-hidden p-0">
                        <div className="flex items-center gap-2 rounded-t-xl border-b bg-neutral-50/50 px-6 py-3 dark:bg-neutral-800/30">
                            <Upload className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                            <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                Logo de la Institución
                            </span>
                        </div>
                        <CardContent className="flex justify-center p-6">
                            <InstitutionLogoUpload
                                logoUrl={institution?.logo_url ?? null}
                                institutionName={institution?.name ?? ''}
                            />
                        </CardContent>
                    </Card>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
