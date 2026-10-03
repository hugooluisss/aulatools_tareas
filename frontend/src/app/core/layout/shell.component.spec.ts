import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter, Router } from '@angular/router';
import { ShellComponent } from './shell.component';
import { TokenStorageService } from '../auth/token-storage.service';

describe('ShellComponent', () => {
  it('shows role-specific navigation', async () => {
    TestBed.configureTestingModule({
      imports: [ShellComponent],
      providers: [provideRouter([]), provideHttpClient(), provideHttpClientTesting()],
    });
    const token = TestBed.inject(TokenStorageService);
    token.setToken(`e30.${btoa(JSON.stringify({ role: 'student' }))}.x`);
    const fixture = TestBed.createComponent(ShellComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Mis tareas');
    expect(fixture.nativeElement.textContent).not.toContain('Profesores');
    expect(fixture.nativeElement.textContent).not.toContain('Inicio');
    expect(fixture.nativeElement.textContent).not.toContain('Control escolar');
    expect(fixture.nativeElement.textContent).not.toContain('Catálogos');
    expect(fixture.nativeElement.textContent).not.toContain('Reportes');
  });

  it('shows collapsible admin groups and opens the active group', () => {
    TestBed.configureTestingModule({
      imports: [ShellComponent],
      providers: [provideRouter([]), provideHttpClient(), provideHttpClientTesting()],
    });
    const token = TestBed.inject(TokenStorageService);
    token.setToken(`e30.${btoa(JSON.stringify({ role: 'admin' }))}.x`);
    const fixture = TestBed.createComponent(ShellComponent);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Control escolar');
    expect(fixture.nativeElement.textContent).toContain('Catálogos');
    expect(fixture.nativeElement.textContent).toContain('Reportes');
    expect(fixture.nativeElement.querySelectorAll('details').length).toBe(4);
    expect(fixture.nativeElement.textContent).toContain('Usuarios');
    expect(fixture.nativeElement.textContent).toContain('Generales');
    expect(fixture.nativeElement.querySelectorAll('a[href="/calendar"]')).toHaveLength(1);
    expect(fixture.nativeElement.textContent).not.toContain('Inicio');
    const nav = fixture.nativeElement.querySelector('.app-sidebar__nav');
    expect(
      Array.from(nav.children).map(
        (item: any) => item.querySelector('summary')?.textContent.trim() ?? item.textContent.trim(),
      ),
    ).toEqual(['Control escolar', 'Catálogos', 'Reportes', 'Tareas', 'Escuela']);
  });

  it('clears the session and goes to login when logging out', () => {
    TestBed.configureTestingModule({
      imports: [ShellComponent],
      providers: [provideRouter([]), provideHttpClient(), provideHttpClientTesting()],
    });
    const token = TestBed.inject(TokenStorageService);
    token.setToken(`e30.${btoa(JSON.stringify({ role: 'teacher' }))}.x`);
    const router = TestBed.inject(Router);
    const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);
    const fixture = TestBed.createComponent(ShellComponent);
    fixture.detectChanges();

    fixture.nativeElement.querySelector('button[aria-label="Cerrar sesión"]').click();

    expect(token.getRole()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/login']);
  });
});
