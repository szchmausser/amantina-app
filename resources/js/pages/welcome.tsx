import { Head, Link, usePage } from "@inertiajs/react";
import { dashboard, login, register } from "@/routes";

export default function Welcome({
	canRegister = true,
}: {
	canRegister?: boolean;
}) {
	const { auth, institution } = usePage().props;

	return (
		<>
			<Head title="Welcome">
				<link rel="preconnect" href="https://fonts.bunny.net" />
				<link
					href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
					rel="stylesheet"
				/>
			</Head>
			<div className="flex min-h-screen flex-col items-center bg-secondary p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:text-[#EDEDEC]">
				<header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
					<nav className="flex items-center justify-end gap-4">
						{auth.user ? (
							<Link
								href={dashboard()}
								className="inline-block rounded-sm border border-[#19140035] bg-white px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-card dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
							>
								Dashboard
							</Link>
						) : (
							<>
								<Link
									href={login()}
									className="inline-block rounded-sm border border-[#19140035] bg-white px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-card dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
								>
									Log in
								</Link>
								{canRegister && (
									<Link
										href={register()}
										className="inline-block rounded-sm border border-[#19140035] bg-white px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-card dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
									>
										Register
									</Link>
								)}
							</>
						)}
					</nav>
				</header>
				<div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
					<main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
						<div className="flex flex-1 flex-col items-center justify-center rounded-br-lg rounded-bl-lg bg-primary p-8 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-12 dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
							<h1 className="text-center text-lg text-primary-foreground">
								Software para el registro de las horas socioproductivas
								realizadas por los estudiantes del{" "}
								<strong>C.E &ldquo;Amantina de Sucre</strong>&rdquo;
							</h1>
						</div>
						<div className="relative flex aspect-[335/376] w-full shrink-0 items-center justify-center overflow-hidden rounded-t-lg bg-white lg:mb-0 lg:-ml-px lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-card">
							<img
								src={
									institution?.logo_url ?? "/logos/amantina_logo_gpt_alpha.png"
								}
								alt="Amantina Logo"
								className="h-full w-full object-scale-down p-6 lg:p-10"
							/>
							<div className="pointer-events-none absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-t-none lg:rounded-r-lg dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]" />
						</div>
					</main>
				</div>
				<div className="hidden h-14.5 lg:block"></div>
			</div>
		</>
	);
}
