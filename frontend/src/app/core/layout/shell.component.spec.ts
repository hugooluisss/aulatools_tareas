import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';
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
    expect(fixture.nativeElement.querySelectorAll('details').length).toBe(3);
    expect(fixture.nativeElement.textContent).not.toContain('Inicio');
  });
});
