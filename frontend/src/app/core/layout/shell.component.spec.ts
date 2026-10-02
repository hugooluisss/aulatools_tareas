import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { ShellComponent } from './shell.component';
import { TokenStorageService } from '../auth/token-storage.service';

describe('ShellComponent', () => {
  it('shows role-specific navigation', async () => {
    TestBed.configureTestingModule({ imports: [ShellComponent], providers: [provideRouter([])] });
    const token = TestBed.inject(TokenStorageService);
    token.setToken(`e30.${btoa(JSON.stringify({ role: 'student' }))}.x`);
    const fixture = TestBed.createComponent(ShellComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('Mis tareas');
    expect(fixture.nativeElement.textContent).not.toContain('Profesores');
  });
});
