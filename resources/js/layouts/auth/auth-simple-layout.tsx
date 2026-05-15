import { Link } from "@inertiajs/react";
import { home } from "@/routes";
import type { AuthLayoutProps } from "@/types";

export default function AuthSimpleLayout({
	children,
	title,
	description,
}: AuthLayoutProps) {
	return (
		<div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-secondary px-6 pt-0 pb-6 md:px-10 md:pt-0 md:pb-10">
			<div className="w-full max-w-sm">
				<div className="flex flex-col gap-0">
					<Link href={home()} className="flex justify-center">
						<img
							src="/logos/amantina_logo_gpt_alpha.png"
							alt="Amantina"
							className="h-48 w-auto object-contain"
						/>
					</Link>

					<div className="rounded-xl bg-white p-8 shadow-sm dark:bg-card">
						<div className="mb-6 text-center">
							<h1 className="text-xl font-medium">{title}</h1>
							<p className="text-sm text-muted-foreground">{description}</p>
						</div>
						{children}
					</div>
				</div>
			</div>
		</div>
	);
}
