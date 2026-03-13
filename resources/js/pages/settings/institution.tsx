import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import { Check, Mail, MapPin, Phone, Building2, Fingerprint } from 'lucide-react';
import InstitutionController from '@/actions/App/Http/Controllers/Settings/InstitutionController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    } | null;
}

export default function InstitutionSettings({ institution }: InstitutionProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Datos institucionales" />

            <h1 className="sr-only">Datos institucionales</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Información de la Institución"
                        description="Gestiona los datos de contacto y parámetros generales de la institución"
                    />

                    <Form
                        {...InstitutionController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name" className="flex items-center gap-2">
                                            <Building2 className="h-4 w-4" />
                                            Nombre de la Institución
                                        </Label>
                                        <Input
                                            id="name"
                                            className="mt-1 block w-full"
                                            defaultValue={institution?.name}
                                            name="name"
                                            required
                                            placeholder="Ej: Amanita de Sucre"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="code" className="flex items-center gap-2">
                                            <Fingerprint className="h-4 w-4" />
                                            Código Institucional
                                        </Label>
                                        <Input
                                            id="code"
                                            className="mt-1 block w-full"
                                            defaultValue={institution?.code}
                                            name="code"
                                            placeholder="Ej: AM-001"
                                        />
                                        <InputError message={errors.code} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email" className="flex items-center gap-2">
                                            <Mail className="h-4 w-4" />
                                            Correo Electrónico
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            className="mt-1 block w-full"
                                            defaultValue={institution?.email}
                                            name="email"
                                            placeholder="contacto@institucion.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone" className="flex items-center gap-2">
                                            <Phone className="h-4 w-4" />
                                            Teléfono de Contacto
                                        </Label>
                                        <Input
                                            id="phone"
                                            className="mt-1 block w-full"
                                            defaultValue={institution?.phone}
                                            name="phone"
                                            placeholder="0412-0000000"
                                        />
                                        <InputError message={errors.phone} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="address" className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4" />
                                        Dirección
                                    </Label>
                                    <Input
                                        id="address"
                                        className="mt-1 block w-full"
                                        defaultValue={institution?.address}
                                        name="address"
                                        placeholder="Dirección física completa"
                                    />
                                    <InputError message={errors.address} />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        Guardar cambios
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                            <Check className="h-4 w-4" />
                                            Guardado correctamente
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
