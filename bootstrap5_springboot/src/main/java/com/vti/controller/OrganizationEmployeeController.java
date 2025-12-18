package com.vti.controller;

import dto.EmployeeDTO;
import dto.CreateEmployeeRequest;
import dto.UpdateEmployeeRequest;
import com.vti.service.OrganizationEmployeeService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/organizations")
public class OrganizationEmployeeController {
    
    @Autowired
    private OrganizationEmployeeService organizationEmployeeService;
    
    @GetMapping("/{organizationId}/employees")
    public ResponseEntity<List<EmployeeDTO>> getOrganizationEmployees(
            @PathVariable Integer organizationId,
            @AuthenticationPrincipal UserDetails userDetails) {
        try {
            List<EmployeeDTO> employees = organizationEmployeeService.getOrganizationEmployees(organizationId, userDetails);
            return ResponseEntity.ok(employees);
        } catch (Exception e) {
            return ResponseEntity.badRequest().build();
        }
    }
    
    @PostMapping("/{organizationId}/employees")
    public ResponseEntity<?> createEmployee(
            @PathVariable Integer organizationId,
            @RequestBody CreateEmployeeRequest request,
            @AuthenticationPrincipal UserDetails userDetails) {
        try {
            organizationEmployeeService.createEmployee(organizationId, request, userDetails);
            return ResponseEntity.ok().build();
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(e.getMessage());
        }
    }
    
    @PutMapping("/{organizationId}/employees/{employeeId}")
    public ResponseEntity<?> updateEmployee(
            @PathVariable Integer organizationId,
            @PathVariable Integer employeeId,
            @RequestBody UpdateEmployeeRequest request,
            @AuthenticationPrincipal UserDetails userDetails) {
        try {
            organizationEmployeeService.updateEmployee(organizationId, employeeId, request, userDetails);
            return ResponseEntity.ok().build();
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(e.getMessage());
        }
    }
    
    @DeleteMapping("/{organizationId}/employees/{employeeId}")
    public ResponseEntity<?> deleteEmployee(
            @PathVariable Integer organizationId,
            @PathVariable Integer employeeId,
            @AuthenticationPrincipal UserDetails userDetails) {
        try {
            organizationEmployeeService.deleteEmployee(organizationId, employeeId, userDetails);
            return ResponseEntity.ok().build();
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(e.getMessage());
        }
    }
}